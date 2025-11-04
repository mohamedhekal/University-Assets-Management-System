<?php
// Start output buffering to prevent any accidental output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('staff');

$db = getDB();
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
        // Check if code exists
        if (!empty($code)) {
            $existing = $db->fetch("SELECT id FROM locations WHERE code = ?", [$code]);
            if ($existing) {
                $error = 'كود الموقع موجود مسبقاً';
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO locations (
                department_id, type, name, code, building, floor, room_number, 
                capacity, responsible_person, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
                $status
            ];

            if ($db->query($sql, $params)) {
                $locationId = $db->lastInsertId();

                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'create', 'location', $locationId, "Created location: $name"]
                );

                setAlert('تم إضافة الموقع بنجاح', 'success');
                // Clear any output buffer before redirect
                ob_end_clean();
                header('Location: ' . SITE_URL . '/modules/locations/index.php');
                exit;
            } else {
                $error = 'حدث خطأ أثناء إضافة الموقع';
            }
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'إضافة موقع';
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
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> إضافة موقع جديد</h5>
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
                                    <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['campus_name'] . ' - ' . $dept['faculty_name'] . ' - ' . $dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نوع الموقع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="lab" <?php echo (isset($_POST['type']) && $_POST['type'] == 'lab') ? 'selected' : ''; ?>>مختبر</option>
                            <option value="office" <?php echo (isset($_POST['type']) && $_POST['type'] == 'office') ? 'selected' : ''; ?>>مكتب</option>
                            <option value="room" <?php echo (isset($_POST['type']) && $_POST['type'] == 'room') ? 'selected' : ''; ?>>غرفة</option>
                            <option value="storage" <?php echo (isset($_POST['type']) && $_POST['type'] == 'storage') ? 'selected' : ''; ?>>مستودع</option>
                            <option value="workshop" <?php echo (isset($_POST['type']) && $_POST['type'] == 'workshop') ? 'selected' : ''; ?>>ورشة</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم الموقع <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كود الموقع</label>
                        <input type="text" name="code" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">المبنى</label>
                        <input type="text" name="building" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['building'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الطابق</label>
                        <input type="number" name="floor" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['floor'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">رقم الغرفة</label>
                        <input type="text" name="room_number" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['room_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">السعة</label>
                        <input type="number" name="capacity" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['capacity'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الشخص المسؤول</label>
                        <input type="text" name="responsible_person" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['responsible_person'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                            <option value="under_maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] == 'under_maintenance') ? 'selected' : ''; ?>>تحت الصيانة</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/locations/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>