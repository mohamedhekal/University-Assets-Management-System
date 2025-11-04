<?php
$pageTitle = 'النقل والإعارة';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get transfers
$transfers = $db->fetchAll(
    "SELECT at.*, 
     a.asset_code, a.name as asset_name,
     fl.name as from_location_name, tl.name as to_location_name,
     u.full_name as transferred_by_name
     FROM asset_transfers at
     INNER JOIN assets a ON at.asset_id = a.id
     LEFT JOIN locations fl ON at.from_location_id = fl.id
     LEFT JOIN locations tl ON at.to_location_id = tl.id
     LEFT JOIN users u ON at.transferred_by = u.id
     ORDER BY at.transfer_date DESC
     LIMIT ? OFFSET ?",
    [$limit, $offset]
);

// Get active loans
$loans = $db->fetchAll(
    "SELECT al.*, 
     a.asset_code, a.name as asset_name,
     u.full_name as loaned_to_name,
     admin.full_name as loaned_by_name
     FROM asset_loans al
     INNER JOIN assets a ON al.asset_id = a.id
     LEFT JOIN users u ON al.loaned_to_user_id = u.id
     LEFT JOIN users admin ON al.loaned_by = admin.id
     WHERE al.status = 'active'
     ORDER BY al.loan_date DESC
     LIMIT 10"
);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Transfers -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> سجل النقل</h5>
                    <a href="<?php echo SITE_URL; ?>/modules/transfers/add.php" class="btn btn-light">
                        <i class="bi bi-plus-circle"></i> نقل أصل
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الأصل</th>
                                    <th>من</th>
                                    <th>إلى</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transfers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد عمليات نقل</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transfers as $transfer): ?>
                                        <tr>
                                            <td><?php echo formatDate($transfer['transfer_date']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($transfer['asset_code']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transfer['asset_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($transfer['from_location_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($transfer['to_location_name'] ?? '-'); ?></td>
                                            <td><?php echo formatStatus($transfer['status']); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/modules/transfers/view.php?id=<?php echo $transfer['id']; ?>"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-hand-thumbs-up"></i> الإعارات النشطة</h5>
                    <a href="<?php echo SITE_URL; ?>/modules/transfers/loan.php" class="btn btn-sm btn-light">
                        <i class="bi bi-plus-circle"></i> إعارة
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($loans)): ?>
                        <p class="text-muted text-center">لا توجد إعارات نشطة</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($loans as $loan): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($loan['asset_code']); ?></h6>
                                        <small><?php echo formatDate($loan['loan_date']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($loan['asset_name']); ?></p>
                                    <small>
                                        معار إلى: <?php echo htmlspecialchars($loan['loaned_to_name']); ?><br>
                                        <?php if ($loan['expected_return_date']): ?>
                                            تاريخ العودة المتوقع: <?php
                                                                    $returnDate = strtotime($loan['expected_return_date']);
                                                                    $daysLeft = floor(($returnDate - time()) / 86400);
                                                                    echo formatDate($loan['expected_return_date']);
                                                                    if ($daysLeft < 0) {
                                                                        echo ' <span class="badge bg-danger">متأخر ' . abs($daysLeft) . ' يوم</span>';
                                                                    }
                                                                    ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>