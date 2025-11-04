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
    header('Location: ' . SITE_URL . '/modules/maintenance/index.php');
    exit;
}

$record = $db->fetch("SELECT * FROM maintenance_records WHERE id = ?", [$id]);

if (!$record) {
    setAlert('سجل الصيانة غير موجود', 'danger');
    ob_end_clean();
    header('Location: ' . SITE_URL . '/modules/maintenance/index.php');
    exit;
}

$error = '';

// Process POST data BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_type = sanitizeInput($_POST['maintenance_type'] ?? '');
    $maintenance_date = sanitizeInput($_POST['maintenance_date'] ?? '');
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
    $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : 0;
    $service_provider = sanitizeInput($_POST['service_provider'] ?? '');
    $technician_name = sanitizeInput($_POST['technician_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'pending');

    if (empty($maintenance_type) || empty($maintenance_date)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        $sql = "UPDATE maintenance_records SET
            maintenance_type = ?, maintenance_date = ?, next_maintenance_date = ?,
            cost = ?, service_provider = ?, technician_name = ?, description = ?, status = ?
            WHERE id = ?";

        $params = [
            $maintenance_type,
            $maintenance_date,
            $next_maintenance_date,
            $cost,
            $service_provider ?: null,
            $technician_name ?: null,
            $description ?: null,
            $status,
            $id
        ];

        if ($db->query($sql, $params)) {
            // Update asset if status changed
            if ($status === 'completed' && $record['status'] !== 'completed') {
                $updateAssetSql = "UPDATE assets SET 
                    last_maintenance_date = ?,
                    next_maintenance_date = ?,
                    status = CASE WHEN status = 'maintenance' THEN 'active' ELSE status END
                    WHERE id = ?";
                $db->query($updateAssetSql, [$maintenance_date, $next_maintenance_date, $record['asset_id']]);
            }

            // Log activity
            $db->query(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                [$_SESSION['user_id'], 'update', 'maintenance', $id, "Updated maintenance record"]
            );

            setAlert('تم تحديث سجل الصيانة بنجاح', 'success');
            // Clear any output buffer before redirect
            ob_end_clean();
            header('Location: ' . SITE_URL . '/modules/maintenance/view.php?id=' . $id);
            exit;
        } else {
            $error = 'حدث خطأ أثناء تحديث سجل الصيانة';
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'تعديل سجل الصيانة';
require_once __DIR__ . '/../../includes/header.php';

$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $record;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-pencil"></i> تعديل سجل الصيانة</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نوع الصيانة <span class="text-danger">*</span></label>
                        <select name="maintenance_type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="preventive" <?php echo ($formData['maintenance_type'] ?? '') == 'preventive' ? 'selected' : ''; ?>>وقائية</option>
                            <option value="corrective" <?php echo ($formData['maintenance_type'] ?? '') == 'corrective' ? 'selected' : ''; ?>>تصحيحية</option>
                            <option value="upgrade" <?php echo ($formData['maintenance_type'] ?? '') == 'upgrade' ? 'selected' : ''; ?>>ترقية</option>
                            <option value="inspection" <?php echo ($formData['maintenance_type'] ?? '') == 'inspection' ? 'selected' : ''; ?>>فحص</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ الصيانة <span class="text-danger">*</span></label>
                        <input type="date" name="maintenance_date" class="form-control" required
                            value="<?php echo htmlspecialchars($formData['maintenance_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ الصيانة القادمة</label>
                        <input type="date" name="next_maintenance_date" class="form-control"
                            value="<?php echo htmlspecialchars($formData['next_maintenance_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">التكلفة</label>
                        <input type="number" step="0.01" name="cost" class="form-control"
                            value="<?php echo htmlspecialchars($formData['cost'] ?? '0'); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php echo ($formData['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                            <option value="in_progress" <?php echo ($formData['status'] ?? '') == 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                            <option value="completed" <?php echo ($formData['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>مكتملة</option>
                            <option value="cancelled" <?php echo ($formData['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">مقدم الخدمة</label>
                        <input type="text" name="service_provider" class="form-control"
                            value="<?php echo htmlspecialchars($formData['service_provider'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم الفني</label>
                        <input type="text" name="technician_name" class="form-control"
                            value="<?php echo htmlspecialchars($formData['technician_name'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ التغييرات
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/maintenance/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>