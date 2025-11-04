<?php
$pageTitle = 'الإعدادات';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
requirePermission('admin');

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle settings update here
    $success = 'تم حفظ الإعدادات بنجاح';
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> إعدادات النظام</h5>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> صفحة الإعدادات قيد التطوير
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>