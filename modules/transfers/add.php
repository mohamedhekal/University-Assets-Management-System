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
    $from_location_id = !empty($_POST['from_location_id']) ? (int)$_POST['from_location_id'] : null;
    $to_location_id = (int)($_POST['to_location_id'] ?? 0);
    $from_user_id = !empty($_POST['from_user_id']) ? (int)$_POST['from_user_id'] : null;
    $to_user_id = !empty($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : null;
    $transfer_date = sanitizeInput($_POST['transfer_date'] ?? date('Y-m-d'));
    $reason = sanitizeInput($_POST['reason'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    if ($asset_id <= 0 || $to_location_id <= 0) {
        $error = 'يرجى اختيار الأصل والموقع الهدف';
    } else {
        // Get asset current location
        $asset = $db->fetch("SELECT location_id, assigned_to_user_id FROM assets WHERE id = ?", [$asset_id]);
        if (!$asset) {
            $error = 'الأصل غير موجود';
        } else {
            // Use current location if from_location not specified
            if (empty($from_location_id)) {
                $from_location_id = $asset['location_id'];
            }
            if (empty($from_user_id)) {
                $from_user_id = $asset['assigned_to_user_id'];
            }

            // Get target location info
            $toLocation = $db->fetch(
                "SELECT l.*, d.id as department_id, f.id as faculty_id, c.id as campus_id
                 FROM locations l
                 LEFT JOIN departments d ON l.department_id = d.id
                 LEFT JOIN faculties f ON d.faculty_id = f.id
                 LEFT JOIN campuses c ON f.campus_id = c.id
                 WHERE l.id = ?",
                [$to_location_id]
            );

            // Insert transfer record
            $sql = "INSERT INTO asset_transfers (
                asset_id, from_location_id, to_location_id, from_user_id, to_user_id,
                transfer_date, reason, transferred_by, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";

            $params = [
                $asset_id,
                $from_location_id,
                $to_location_id,
                $from_user_id,
                $to_user_id,
                $transfer_date,
                $reason ?: null,
                $_SESSION['user_id'],
                $notes ?: null
            ];

            if ($db->query($sql, $params)) {
                $transferId = $db->lastInsertId();

                // Update asset location
                $updateSql = "UPDATE assets SET 
                    location_id = ?,
                    department_id = ?,
                    faculty_id = ?,
                    campus_id = ?,
                    assigned_to_user_id = ?,
                    assigned_date = ?
                    WHERE id = ?";

                $db->query($updateSql, [
                    $to_location_id,
                    $toLocation['department_id'] ?? null,
                    $toLocation['faculty_id'] ?? null,
                    $toLocation['campus_id'] ?? null,
                    $to_user_id,
                    $transfer_date,
                    $asset_id
                ]);

                // Complete transfer
                $db->query("UPDATE asset_transfers SET status = 'completed' WHERE id = ?", [$transferId]);

                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'transfer', 'asset', $asset_id, "Asset transferred"]
                );

                setAlert('تم نقل الأصل بنجاح', 'success');
                // Clear all output buffers before redirect
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Location: ' . SITE_URL . '/modules/assets/view.php?id=' . $asset_id);
                exit;
            } else {
                $error = 'حدث خطأ أثناء نقل الأصل';
            }
        }
    }
}

// Now include header after POST processing is done
// Clean all output buffers before including header (discard any accidental output)
while (ob_get_level()) {
    ob_end_clean();
}
$pageTitle = 'نقل أصل';
require_once __DIR__ . '/../../includes/header.php';

// Get assets
$assets = $db->fetchAll("SELECT id, asset_code, name, location_id FROM assets WHERE status != 'retired' ORDER BY asset_code");

// Get locations
$locations = $db->fetchAll(
    "SELECT l.*, d.name as department_name, f.name as faculty_name, c.name as campus_name
     FROM locations l
     LEFT JOIN departments d ON l.department_id = d.id
     LEFT JOIN faculties f ON d.faculty_id = f.id
     LEFT JOIN campuses c ON f.campus_id = c.id
     WHERE l.status = 'active'
     ORDER BY c.name, f.name, d.name, l.name"
);

// Get users
$users = $db->fetchAll("SELECT id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> نقل أصل</h5>
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
                                    data-current-location="<?php echo $asset['location_id']; ?>"
                                    <?php echo ($asset_id == $asset['id'] || (isset($_POST['asset_id']) && $_POST['asset_id'] == $asset['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($asset['asset_code'] . ' - ' . $asset['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الموقع الهدف <span class="text-danger">*</span></label>
                        <select name="to_location_id" class="form-select" required>
                            <option value="">اختر الموقع</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['id']; ?>"
                                    <?php echo (isset($_POST['to_location_id']) && $_POST['to_location_id'] == $loc['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['campus_name'] . ' - ' . $loc['faculty_name'] . ' - ' . $loc['department_name'] . ' - ' . $loc['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">معين إلى (اختياري)</label>
                        <select name="to_user_id" class="form-select">
                            <option value="">غير معين</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ النقل <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['transfer_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">سبب النقل</label>
                        <textarea name="reason" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> نقل
                        </button>
                        <a href="<?php echo SITE_URL; ?>/modules/transfers/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>