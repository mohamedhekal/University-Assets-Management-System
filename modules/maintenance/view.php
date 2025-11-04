<?php
$pageTitle = 'تفاصيل سجل الصيانة';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/maintenance/index.php');
    exit;
}

$record = $db->fetch(
    "SELECT mr.*, 
     a.asset_code, a.name as asset_name, a.serial_number,
     u.full_name as performed_by_name
     FROM maintenance_records mr
     INNER JOIN assets a ON mr.asset_id = a.id
     LEFT JOIN users u ON mr.performed_by = u.id
     WHERE mr.id = ?",
    [$id]
);

if (!$record) {
    setAlert('سجل الصيانة غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/maintenance/index.php');
    exit;
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> تفاصيل سجل الصيانة</h5>
            <div>
                <a href="<?php echo SITE_URL; ?>/modules/maintenance/edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i> تعديل
                </a>
                <a href="<?php echo SITE_URL; ?>/modules/maintenance/index.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> رجوع
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>الأصل:</strong><br>
                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($record['asset_code']); ?></span><br>
                    <small class="text-muted"><?php echo htmlspecialchars($record['asset_name']); ?></small>
                </div>
                <div class="col-md-6">
                    <strong>الحالة:</strong><br>
                    <?php echo formatStatus($record['status']); ?>
                </div>

                <div class="col-md-6">
                    <strong>نوع الصيانة:</strong><br>
                    <span class="badge bg-info"><?php echo htmlspecialchars($record['maintenance_type']); ?></span>
                </div>
                <div class="col-md-6">
                    <strong>تاريخ الصيانة:</strong><br>
                    <?php echo formatDate($record['maintenance_date']); ?>
                </div>

                <?php if (!empty($record['next_maintenance_date'])): ?>
                    <div class="col-md-6">
                        <strong>تاريخ الصيانة القادمة:</strong><br>
                        <?php echo formatDate($record['next_maintenance_date']); ?>
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <strong>التكلفة:</strong><br>
                    <?php echo formatCurrency($record['cost']); ?>
                </div>

                <?php if (!empty($record['service_provider'])): ?>
                    <div class="col-md-6">
                        <strong>مقدم الخدمة:</strong><br>
                        <?php echo htmlspecialchars($record['service_provider']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($record['technician_name'])): ?>
                    <div class="col-md-6">
                        <strong>اسم الفني:</strong><br>
                        <?php echo htmlspecialchars($record['technician_name']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($record['description'])): ?>
                    <div class="col-12">
                        <hr>
                    </div>
                    <div class="col-12">
                        <strong>الوصف:</strong><br>
                        <?php echo nl2br(htmlspecialchars($record['description'])); ?>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <hr>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>تم التنفيذ بواسطة:</strong> <?php echo htmlspecialchars($record['performed_by_name'] ?? '-'); ?><br>
                        <strong>تاريخ الإنشاء:</strong> <?php echo formatDate($record['created_at'], 'Y-m-d H:i'); ?>
                    </small>
                </div>
            </div>

            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>/modules/assets/view.php?id=<?php echo $record['asset_id']; ?>" class="btn btn-info">
                    <i class="bi bi-box-seam"></i> عرض الأصل
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>