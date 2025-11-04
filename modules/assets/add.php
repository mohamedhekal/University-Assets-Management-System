<?php
// Start output buffering to prevent any accidental output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('staff');

$db = getDB();
$error = '';
$success = false;

// Process POST data BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    $asset_code = sanitizeInput($_POST['asset_code'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $brand = sanitizeInput($_POST['brand'] ?? '');
    $model = sanitizeInput($_POST['model'] ?? '');
    $serial_number = sanitizeInput($_POST['serial_number'] ?? '');
    $barcode = sanitizeInput($_POST['barcode'] ?? '');

    // Location
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    $faculty_id = !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;

    // Financial
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0;
    $current_value = !empty($_POST['current_value']) ? (float)$_POST['current_value'] : $purchase_price;
    $depreciation_rate = !empty($_POST['depreciation_rate']) ? (float)$_POST['depreciation_rate'] : 0;

    // Warranty
    $warranty_start_date = !empty($_POST['warranty_start_date']) ? $_POST['warranty_start_date'] : null;
    $warranty_end_date = !empty($_POST['warranty_end_date']) ? $_POST['warranty_end_date'] : null;
    $warranty_provider = sanitizeInput($_POST['warranty_provider'] ?? '');

    // Maintenance
    $maintenance_interval_days = !empty($_POST['maintenance_interval_days']) ? (int)$_POST['maintenance_interval_days'] : 90;
    $next_maintenance_date = null;
    if ($maintenance_interval_days > 0) {
        $next_maintenance_date = date('Y-m-d', strtotime("+$maintenance_interval_days days"));
    }

    // Status & Assignment
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $assigned_to_user_id = !empty($_POST['assigned_to_user_id']) ? (int)$_POST['assigned_to_user_id'] : null;
    $assigned_date = !empty($_POST['assigned_date']) ? $_POST['assigned_date'] : null;

    // Additional
    $supplier = sanitizeInput($_POST['supplier'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Validation
    if (empty($asset_code) || empty($name) || $category_id <= 0) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        // Check if asset code exists
        $existing = $db->fetch("SELECT id FROM assets WHERE asset_code = ?", [$asset_code]);
        if ($existing) {
            $error = 'كود الأصل موجود مسبقاً';
        } else {
            // Generate QR Code Data
            $qr_code = generateQRCodeData(0, $asset_code); // Will be updated after insert

            // Insert asset
            $sql = "INSERT INTO assets (
                asset_code, category_id, name, description, brand, model, serial_number, barcode, qr_code,
                location_id, campus_id, faculty_id, department_id,
                purchase_date, purchase_price, current_value, depreciation_rate,
                warranty_start_date, warranty_end_date, warranty_provider,
                maintenance_interval_days, next_maintenance_date,
                status, assigned_to_user_id, assigned_date,
                supplier, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $asset_code,
                $category_id,
                $name,
                $description,
                $brand,
                $model,
                $serial_number,
                $barcode,
                $qr_code,
                $location_id,
                $campus_id,
                $faculty_id,
                $department_id,
                $purchase_date,
                $purchase_price,
                $current_value,
                $depreciation_rate,
                $warranty_start_date,
                $warranty_end_date,
                $warranty_provider,
                $maintenance_interval_days,
                $next_maintenance_date,
                $status,
                $assigned_to_user_id,
                $assigned_date,
                $supplier,
                $notes,
                $_SESSION['user_id']
            ];

            if ($db->query($sql, $params)) {
                $assetId = $db->lastInsertId();

                // Update QR Code with actual ID
                $qr_code = generateQRCodeData($assetId, $asset_code);
                $db->query("UPDATE assets SET qr_code = ? WHERE id = ?", [$qr_code, $assetId]);

                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'create', 'asset', $assetId, "Created asset: $asset_code"]
                );

                setAlert('تم إضافة الأصل بنجاح', 'success');
                // Clear any output buffer before redirect
                ob_end_clean();
                header('Location: ' . SITE_URL . '/modules/assets/view.php?id=' . $assetId);
                exit;
            } else {
                $error = 'حدث خطأ أثناء إضافة الأصل';
            }
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'إضافة أصل جديد';
require_once __DIR__ . '/../../includes/header.php';

// Get categories
$categories = $db->fetchAll("SELECT id, name, type FROM asset_categories WHERE status = 'active' ORDER BY name");

// Get campuses
$campuses = $db->fetchAll("SELECT id, name FROM campuses WHERE status = 'active' ORDER BY name");

// Get users for assignment
$users = $db->fetchAll("SELECT id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> إضافة أصل جديد</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">المعلومات الأساسية</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">كود الأصل <span class="text-danger">*</span></label>
                        <input type="text" name="asset_code" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['asset_code'] ?? ''); ?>">
                        <small class="text-muted">يجب أن يكون فريد</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">اختر التصنيف</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">اسم الأصل <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">العلامة التجارية</label>
                        <input type="text" name="brand" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الموديل</label>
                        <input type="text" name="model" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرقم التسلسلي</label>
                        <input type="text" name="serial_number" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['serial_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الباركود</label>
                        <input type="text" name="barcode" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                        </select>
                    </div>

                    <!-- Location Information -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2 mb-3">الموقع</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الحرم</label>
                        <select name="campus_id" class="form-select" id="campus_id">
                            <option value="">اختر الحرم</option>
                            <?php foreach ($campuses as $campus): ?>
                                <option value="<?php echo $campus['id']; ?>"><?php echo htmlspecialchars($campus['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الموقع المحدد (مختبر/مكتب)</label>
                        <select name="location_id" class="form-select" id="location_id">
                            <option value="">اختر الموقع</option>
                        </select>
                    </div>

                    <!-- Financial Information -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2 mb-3">المعلومات المالية</h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ الشراء</label>
                        <input type="date" name="purchase_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">سعر الشراء</label>
                        <input type="number" step="0.01" name="purchase_price" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['purchase_price'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">القيمة الحالية</label>
                        <input type="number" step="0.01" name="current_value" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['current_value'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">معدل الإهلاك (%)</label>
                        <input type="number" step="0.01" name="depreciation_rate" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['depreciation_rate'] ?? '0'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">المورد</label>
                        <input type="text" name="supplier" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['supplier'] ?? ''); ?>">
                    </div>

                    <!-- Warranty Information -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2 mb-3">الضمان</h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ بداية الضمان</label>
                        <input type="date" name="warranty_start_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['warranty_start_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ نهاية الضمان</label>
                        <input type="date" name="warranty_end_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['warranty_end_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">مقدم الضمان</label>
                        <input type="text" name="warranty_provider" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['warranty_provider'] ?? ''); ?>">
                    </div>

                    <!-- Maintenance Information -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2 mb-3">الصيانة</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">فترة الصيانة (بالأيام)</label>
                        <input type="number" name="maintenance_interval_days" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['maintenance_interval_days'] ?? '90'); ?>">
                    </div>

                    <!-- Assignment -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2 mb-3">التعيين</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">معين إلى</label>
                        <select name="assigned_to_user_id" class="form-select">
                            <option value="">غير معين</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ التعيين</label>
                        <input type="date" name="assigned_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['assigned_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <!-- Notes -->
                    <div class="col-12 mt-4">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> حفظ
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/assets/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Load locations based on campus (AJAX would be needed for full implementation)
    document.getElementById('campus_id').addEventListener('change', function() {
        // This would typically make an AJAX call to load locations
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>