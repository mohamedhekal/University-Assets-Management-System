<?php
// Start output buffering to prevent any accidental output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('admin');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);

if (!$user) {
    setAlert('المستخدم غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

$error = '';

// Process POST data BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'staff');
    $faculty_id = !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');

    // Validation
    if (empty($username) || empty($email) || empty($full_name)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        // Check if username exists (excluding current user)
        $existingUser = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
        if ($existingUser) {
            $error = 'اسم المستخدم موجود مسبقاً';
        } else {
            // Check if email exists (excluding current user)
            $existingEmail = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
            if ($existingEmail) {
                $error = 'البريد الإلكتروني موجود مسبقاً';
            } else {
                // Update password only if provided
                if (!empty($password)) {
                    if ($password !== $password_confirm) {
                        $error = 'كلمات المرور غير متطابقة';
                    } elseif (strlen($password) < 6) {
                        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
                    } else {
                        $hashedPassword = hashPassword($password);
                        $sql = "UPDATE users SET
                            username = ?, email = ?, password = ?, full_name = ?, role = ?, 
                            faculty_id = ?, department_id = ?, phone = ?, status = ?
                            WHERE id = ?";
                        $params = [
                            $username,
                            $email,
                            $hashedPassword,
                            $full_name,
                            $role,
                            $faculty_id,
                            $department_id,
                            $phone ?: null,
                            $status,
                            $id
                        ];
                    }
                } else {
                    // Don't update password
                    $sql = "UPDATE users SET
                        username = ?, email = ?, full_name = ?, role = ?, 
                        faculty_id = ?, department_id = ?, phone = ?, status = ?
                        WHERE id = ?";
                    $params = [
                        $username,
                        $email,
                        $full_name,
                        $role,
                        $faculty_id,
                        $department_id,
                        $phone ?: null,
                        $status,
                        $id
                    ];
                }

                if (empty($error)) {
                    if ($db->query($sql, $params)) {
                        // Log activity
                        $db->query(
                            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                            [$_SESSION['user_id'], 'update', 'user', $id, "Updated user: $username"]
                        );

                        setAlert('تم تحديث المستخدم بنجاح', 'success');
                        // Clear any output buffer before redirect
                        ob_end_clean();
                        header('Location: ' . SITE_URL . '/modules/users/view.php?id=' . $id);
                        exit;
                    } else {
                        $error = 'حدث خطأ أثناء تحديث المستخدم';
                    }
                }
            }
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'تعديل المستخدم';
require_once __DIR__ . '/../../includes/header.php';

// Get faculties and departments for dropdown
$faculties = $db->fetchAll("SELECT * FROM faculties WHERE status = 'active' ORDER BY name");
$departments = $db->fetchAll("SELECT * FROM departments WHERE status = 'active' ORDER BY name");

$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $user;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-pencil"></i> تعديل المستخدم</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control"
                            value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                        <div class="invalid-feedback">يرجى إدخال اسم المستخدم</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                        <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                        <small class="text-muted">اتركه فارغاً إذا لم تريد تغيير كلمة المرور</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirm" class="form-control" minlength="6">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                            value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>" required>
                        <div class="invalid-feedback">يرجى إدخال الاسم الكامل</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الدور <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="staff" <?php echo ($formData['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>موظف</option>
                            <option value="lab_manager" <?php echo ($formData['role'] ?? '') === 'lab_manager' ? 'selected' : ''; ?>>مدير مختبر</option>
                            <option value="faculty_manager" <?php echo ($formData['role'] ?? '') === 'faculty_manager' ? 'selected' : ''; ?>>مدير كلية</option>
                            <option value="admin" <?php echo ($formData['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>مدير</option>
                            <option value="guest" <?php echo ($formData['role'] ?? '') === 'guest' ? 'selected' : ''; ?>>زائر</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?php echo ($formData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo ($formData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الكلية</label>
                        <select name="faculty_id" class="form-select" id="faculty_id">
                            <option value="">اختر الكلية</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo $faculty['id']; ?>"
                                    <?php echo ($formData['faculty_id'] ?? 0) == $faculty['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">القسم</label>
                        <select name="department_id" class="form-select" id="department_id">
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                    data-faculty-id="<?php echo $dept['faculty_id']; ?>"
                                    <?php echo ($formData['department_id'] ?? 0) == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/users/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Filter departments based on selected faculty
    document.getElementById('faculty_id').addEventListener('change', function() {
        const facultyId = this.value;
        const departmentSelect = document.getElementById('department_id');
        const options = departmentSelect.querySelectorAll('option');

        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionFacultyId = option.getAttribute('data-faculty-id');
                option.style.display = (facultyId === '' || optionFacultyId === facultyId) ? 'block' : 'none';
            }
        });

        // Reset department selection if it doesn't match the faculty
        if (facultyId && departmentSelect.value) {
            const selectedOption = departmentSelect.querySelector(`option[value="${departmentSelect.value}"]`);
            if (selectedOption && selectedOption.getAttribute('data-faculty-id') !== facultyId) {
                departmentSelect.value = '';
            }
        }
    });

    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>