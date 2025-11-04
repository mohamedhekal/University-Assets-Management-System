<?php
$pageTitle = 'تفاصيل النقل';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/transfers/index.php');
    exit;
}

$transfer = $db->fetch(
    "SELECT at.*, 
     a.asset_code, a.name as asset_name,
     fl.name as from_location_name, tl.name as to_location_name,
     fu.full_name as from_user_name, tu.full_name as to_user_name,
     u.full_name as transferred_by_name
     FROM asset_transfers at
     INNER JOIN assets a ON at.asset_id = a.id
     LEFT JOIN locations fl ON at.from_location_id = fl.id
     LEFT JOIN locations tl ON at.to_location_id = tl.id
     LEFT JOIN users fu ON at.from_user_id = fu.id
     LEFT JOIN users tu ON at.to_user_id = tu.id
     LEFT JOIN users u ON at.transferred_by = u.id
     WHERE at.id = ?",
    [$id]
);

if (!$transfer) {
    setAlert('عملية النقل غير موجودة', 'danger');
    header('Location: ' . SITE_URL . '/modules/transfers/index.php');
    exit;
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> تفاصيل عملية النقل</h5>
            <a href="<?php echo SITE_URL; ?>/modules/transfers/index.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> رجوع
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>الأصل:</strong><br>
                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($transfer['asset_code']); ?></span><br>
                    <small class="text-muted"><?php echo htmlspecialchars($transfer['asset_name']); ?></small>
                </div>
                <div class="col-md-6">
                    <strong>الحالة:</strong><br>
                    <?php echo formatStatus($transfer['status']); ?>
                </div>

                <div class="col-12">
                    <hr>
                </div>

                <div class="col-md-6">
                    <strong>من الموقع:</strong><br>
                    <?php echo htmlspecialchars($transfer['from_location_name'] ?? '-'); ?>
                </div>
                <div class="col-md-6">
                    <strong>إلى الموقع:</strong><br>
                    <?php echo htmlspecialchars($transfer['to_location_name'] ?? '-'); ?>
                </div>

                <?php if (!empty($transfer['from_user_name']) || !empty($transfer['to_user_name'])): ?>
                    <div class="col-12">
                        <hr>
                    </div>
                    <?php if (!empty($transfer['from_user_name'])): ?>
                        <div class="col-md-6">
                            <strong>من المستخدم:</strong><br>
                            <?php echo htmlspecialchars($transfer['from_user_name']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($transfer['to_user_name'])): ?>
                        <div class="col-md-6">
                            <strong>إلى المستخدم:</strong><br>
                            <?php echo htmlspecialchars($transfer['to_user_name']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="col-12">
                    <hr>
                </div>

                <div class="col-md-6">
                    <strong>تاريخ النقل:</strong><br>
                    <?php echo formatDate($transfer['transfer_date']); ?>
                </div>
                <div class="col-md-6">
                    <strong>تم النقل بواسطة:</strong><br>
                    <?php echo htmlspecialchars($transfer['transferred_by_name']); ?>
                </div>

                <?php if (!empty($transfer['reason'])): ?>
                    <div class="col-12">
                        <hr>
                    </div>
                    <div class="col-12">
                        <strong>سبب النقل:</strong><br>
                        <?php echo nl2br(htmlspecialchars($transfer['reason'])); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($transfer['notes'])): ?>
                    <div class="col-12">
                        <hr>
                    </div>
                    <div class="col-12">
                        <strong>ملاحظات:</strong><br>
                        <?php echo nl2br(htmlspecialchars($transfer['notes'])); ?>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <hr>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>تاريخ الإنشاء:</strong> <?php echo formatDate($transfer['created_at'], 'Y-m-d H:i'); ?>
                    </small>
                </div>
            </div>

            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>/modules/assets/view.php?id=<?php echo $transfer['asset_id']; ?>" class="btn btn-info">
                    <i class="bi bi-box-seam"></i> عرض الأصل
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>