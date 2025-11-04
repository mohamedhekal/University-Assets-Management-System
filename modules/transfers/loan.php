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
    $loaned_to_user_id = (int)($_POST['loaned_to_user_id'] ?? 0);
    $loan_date = sanitizeInput($_POST['loan_date'] ?? date('Y-m-d'));
    $expected_return_date = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
    $loan_purpose = sanitizeInput($_POST['loan_purpose'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    if ($asset_id <= 0 || $loaned_to_user_id <= 0) {
        $error = 'يرجى اختيار الأصل والمستخدم';
    } else {
        // Check if asset is already loaned
        $activeLoan = $db->fetch("SELECT id FROM asset_loans WHERE asset_id = ? AND status = 'active'", [$asset_id]);
        if ($activeLoan) {
            $error = 'هذا الأصل معار حالياً';
        } else {
            // Insert loan record
            $sql = "INSERT INTO asset_loans (
                asset_id, loaned_to_user_id, loan_date, expected_return_date,
                loan_purpose, loaned_by, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)";

            $params = [
                $asset_id,
                $loaned_to_user_id,
                $loan_date,
                $expected_return_date,
                $loan_purpose ?: null,
                $_SESSION['user_id'],
                $notes ?: null
            ];

            if ($db->query($sql, $params)) {
                // Update asset assignment
                $db->query(
                    "UPDATE assets SET assigned_to_user_id = ?, assigned_date = ? WHERE id = ?",
                    [$loaned_to_user_id, $loan_date, $asset_id]
                );

                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'loan', 'asset', $asset_id, "Asset loaned"]
                );

                setAlert('تم إعارة الأصل بنجاح', 'success');
                // Clear any output buffer before redirect
                ob_end_clean();
                header('Location: ' . SITE_URL . '/modules/transfers/index.php');
                exit;
            } else {
                $error = 'حدث خطأ أثناء إعارة الأصل';
            }
        }
    }
}

// Now include header after POST processing is done
// Clean output buffer before including header
ob_end_flush();
$pageTitle = 'إعارة أصل';
require_once __DIR__ . '/../../includes/header.php';

// Get assets (not loaned and not retired)
$assets = $db->fetchAll(
    "SELECT a.id, a.asset_code, a.name 
     FROM assets a
     LEFT JOIN asset_loans al ON a.id = al.asset_id AND al.status = 'active'
     WHERE a.status != 'retired' AND al.id IS NULL
     ORDER BY a.asset_code"
);

// Get users
$users = $db->fetchAll("SELECT id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-hand-thumbs-up"></i> إعارة أصل</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الأصل <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select" required>
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
                        <label class="form-label">معار إلى <span class="text-danger">*</span></label>
                        <select name="loaned_to_user_id" class="form-select" required>
                            <option value="">اختر المستخدم</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                    <?php echo (isset($_POST['loaned_to_user_id']) && $_POST['loaned_to_user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ الإعارة <span class="text-danger">*</span></label>
                        <input type="date" name="loan_date" class="form-control" required
                            value="<?php echo htmlspecialchars($_POST['loan_date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ العودة المتوقع</label>
                        <input type="date" name="expected_return_date" class="form-control"
                            value="<?php echo htmlspecialchars($_POST['expected_return_date'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">الغرض من الإعارة</label>
                        <textarea name="loan_purpose" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['loan_purpose'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> إعارة
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