<?php
// Start output buffering to prevent any accidental output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('staff');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    ob_end_clean();
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

$location = $db->fetch("SELECT * FROM locations WHERE id = ?", [$id]);

if (!$location) {
    setAlert('الموقع غير موجود', 'danger');
    ob_end_clean();
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

$error = '';

// Process POST data BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = (int)($_POST['department_id'] ?? 0);
    $type = sanitizeInput($_POST['type'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $code = sanitizeInput($_POST['code'] ?? '');
    $building = sanitizeInput($_POST['building'] ?? '');
    $floor = !empty($_POST['floor']) ? (int)$_POST['floor'] : null;
    $room_number = sanitizeInput($_POST['room_number'] ?? '');
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    $responsible_person = sanitizeInput($_POST['responsible_person'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');

    if (empty($department_id) || empty($type) || empty($name)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        // Check if code exists (excluding current location)
        if (!empty($code)) {
            $existing = $db->fetch("SELECT id FROM locations WHERE code = ? AND id != ?", [$code, $id]);
            if ($existing) {
                $error = 'كود الموقع موجود مسبقاً';
            }
        }

        if (empty($error)) {
            $sql = "UPDATE locations SET
                department_id = ?, type = ?, name = ?, code = ?, building = ?, 
                floor = ?, room_number = ?, capacity = ?, responsible_person = ?, status = ?
                WHERE id = ?";

            $params = [
                $department_id,
                $type,
                $name,
                $code ?: null,
                $building ?: null,
                $floor,
                $room_number ?: null,
                $capacity,
                $responsible_person ?: null,
                $status,
                $id
            ];

            if ($db->query($sql, $params)) {
                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'update', 'location', $id, "Updated location: $name"]
                );

                setAlert('تم تحديث الموقع بنجاح', 'success');
                // Clear any output buffer before redirect
                ob_end_clean();
                header('Location: ' . SITE_URL . '/modules/locations/view.php?id=' . $id);
                exit;
            } else {
                $error = 'حدث خطأ أثناء تحديث الموقع';
            }
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'تعديل الموقع';
require_once __DIR__ . '/../../includes/header.php';

// Get departments
$departments = $db->fetchAll(
    "SELECT d.*, f.name as faculty_name, c.name as campus_name 
     FROM departments d
     LEFT JOIN faculties f ON d.faculty_id = f.id
     LEFT JOIN campuses c ON f.campus_id = c.id
     WHERE d.status = 'active' 
     ORDER BY c.name, f.name, d.name"
);

$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $location;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-pencil"></i> تعديل الموقع</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">القسم <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                    <?php echo ($formData['department_id'] ?? 0) == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['campus_name'] . ' - ' . $dept['faculty_name'] . ' - ' . $dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نوع الموقع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="lab" <?php echo ($formData['type'] ?? '') == 'lab' ? 'selected' : ''; ?>>مختبر</option>
                            <option value="office" <?php echo ($formData['type'] ?? '') == 'office' ? 'selected' : ''; ?>>مكتب</option>
                            <option value="room" <?php echo ($formData['type'] ?? '') == 'room' ? 'selected' : ''; ?>>غرفة</option>
                            <option value="storage" <?php echo ($formData['type'] ?? '') == 'storage' ? 'selected' : ''; ?>>مستودع</option>
                            <option value="workshop" <?php echo ($formData['type'] ?? '') == 'workshop' ? 'selected' : ''; ?>>ورشة</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم الموقع <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كود الموقع</label>
                        <input type="text" name="code" class="form-control"
                            value="<?php echo htmlspecialchars($formData['code'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">المبنى</label>
                        <input type="text" name="building" class="form-control"
                            value="<?php echo htmlspecialchars($formData['building'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الطابق</label>
                        <input type="number" name="floor" class="form-control"
                            value="<?php echo htmlspecialchars($formData['floor'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم الغرفة</label>
                        <input type="text" name="room_number" class="form-control"
                            value="<?php echo htmlspecialchars($formData['room_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">السعة</label>
                        <input type="number" name="capacity" class="form-control"
                            value="<?php echo htmlspecialchars($formData['capacity'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الشخص المسؤول</label>
                        <input type="text" name="responsible_person" class="form-control"
                            value="<?php echo htmlspecialchars($formData['responsible_person'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($formData['status'] ?? '') == 'active' ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo ($formData['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                            <option value="under_maintenance" <?php echo ($formData['status'] ?? '') == 'under_maintenance' ? 'selected' : ''; ?>>تحت الصيانة</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ التغييرات
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/locations/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>