<?php
// Start output buffering to prevent any accidental output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('staff');

$db = getDB();
$error = '';
$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;

// Process POST data BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = (int)($_POST['asset_id'] ?? 0);
    $maintenance_type = sanitizeInput($_POST['maintenance_type'] ?? '');
    $maintenance_date = sanitizeInput($_POST['maintenance_date'] ?? date('Y-m-d'));
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
    $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : 0;
    $service_provider = sanitizeInput($_POST['service_provider'] ?? '');
    $technician_name = sanitizeInput($_POST['technician_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'pending');

    if ($asset_id <= 0 || empty($maintenance_type) || empty($maintenance_date)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        // Insert maintenance record
        $sql = "INSERT INTO maintenance_records (
            asset_id, maintenance_type, maintenance_date, next_maintenance_date,
            cost, service_provider, technician_name, description, status, performed_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $asset_id,
            $maintenance_type,
            $maintenance_date,
            $next_maintenance_date,
            $cost,
            $service_provider ?: null,
            $technician_name ?: null,
            $description ?: null,
            $status,
            $_SESSION['user_id']
        ];

        if ($db->query($sql, $params)) {
            $maintenanceId = $db->lastInsertId();

            // Update asset's maintenance dates and status
            if ($status === 'completed') {
                $updateAssetSql = "UPDATE assets SET 
                    last_maintenance_date = ?,
                    next_maintenance_date = ?,
                    status = CASE WHEN status = 'maintenance' THEN 'active' ELSE status END
                    WHERE id = ?";
                $db->query($updateAssetSql, [$maintenance_date, $next_maintenance_date, $asset_id]);
            } elseif ($status === 'in_progress') {
                $db->query("UPDATE assets SET status = 'maintenance' WHERE id = ?", [$asset_id]);
            }

            // Log activity
            $db->query(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                [$_SESSION['user_id'], 'create', 'maintenance', $maintenanceId, "Created maintenance record"]
            );

            setAlert('تم إضافة سجل الصيانة بنجاح', 'success');
            // Clear all output buffers before redirect
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Location: ' . SITE_URL . '/modules/maintenance/index.php');
            exit;
        } else {
            $error = 'حدث خطأ أثناء إضافة سجل الصيانة';
        }
    }
}

// Now include header after POST processing is done
// Clean all output buffers before including header (discard any accidental output)
while (ob_get_level()) {
    ob_end_clean();
}
$pageTitle = 'إضافة سجل صيانة';
require_once __DIR__ . '/../../includes/header.php';

// Get assets
$assets = $db->fetchAll("SELECT id, asset_code, name FROM assets WHERE status != 'retired' ORDER BY asset_code");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> إضافة سجل صيانة</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الأصل <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select" required id="asset_id">
                            <option value="">اختر الأصل</option>
                            <?php foreach ($assets as $asset): ?>
                                <option value="<?php echo $asset['id']; ?>"
                                    <?php echo ($asset_id == $asset['id'] || (isset($_POST['asset_id']) && $_POST['asset_id'] == $asset['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($asset['asset_code'] . ' - ' . $asset['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نوع الصيانة <span class="text-danger">*</span></label>
                        <select name="maintenance_type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="preventive" <?php echo (isset($_POST['maintenance_type']) && $_POST['maintenance_type'] == 'preventive') ? 'selected' : ''; ?>>وقائية</option>
                            <option value="corrective" <?php echo (isset($_POST['maintenance_type']) && $_POST['maintenance_type'] == 'corrective') ? 'selected' : ''; ?>>تصحيحية</option>
                            <option value="upgrade" <?php echo (isset($_POST['maintenance_type']) && $_POST['maintenance_type'] == 'upgrade') ? 'selected' : ''; ?>>ترقية</option>
                            <option value="inspection" <?php echo (isset($_POST['maintenance_type']) && $_POST['maintenance_type'] == 'inspection') ? 'selected' : ''; ?>>فحص</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ الصيانة <span class="text-danger">*</span></label>
                        <input type="date" name="maintenance_date" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['maintenance_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ الصيانة القادمة</label>
                        <input type="date" name="next_maintenance_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['next_maintenance_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">التكلفة</label>
                        <input type="number" step="0.01" name="cost" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['cost'] ?? '0'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">مقدم الخدمة</label>
                        <input type="text" name="service_provider" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['service_provider'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم الفني</label>
                        <input type="text" name="technician_name" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['technician_name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'pending') ? 'selected' : ''; ?>>قيد الانتظار</option>
                            <option value="in_progress" <?php echo (isset($_POST['status']) && $_POST['status'] == 'in_progress') ? 'selected' : ''; ?>>قيد التنفيذ</option>
                            <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>مكتملة</option>
                            <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] == 'cancelled') ? 'selected' : ''; ?>>ملغاة</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/maintenance/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>