<?php
$pageTitle = 'التقارير والإحصائيات';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$reportType = sanitizeInput($_GET['type'] ?? 'overview');

// Get statistics based on report type
$stats = [];

switch ($reportType) {
    case 'by_category':
        $stats['data'] = $db->fetchAll(
            "SELECT ac.name, ac.type, COUNT(a.id) as count, SUM(a.current_value) as total_value
             FROM asset_categories ac
             LEFT JOIN assets a ON ac.id = a.category_id AND a.status = 'active'
             GROUP BY ac.id, ac.name, ac.type
             ORDER BY count DESC"
        );
        break;

    case 'by_location':
        $stats['data'] = $db->fetchAll(
            "SELECT l.name, l.type, COUNT(a.id) as count, SUM(a.current_value) as total_value
             FROM locations l
             LEFT JOIN assets a ON l.id = a.location_id AND a.status = 'active'
             GROUP BY l.id, l.name, l.type
             HAVING count > 0
             ORDER BY count DESC"
        );
        break;

    case 'by_status':
        $stats['data'] = $db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(current_value) as total_value
             FROM assets
             GROUP BY status
             ORDER BY count DESC"
        );
        break;

    case 'financial':
        $stats['total_purchase'] = $db->fetch("SELECT SUM(purchase_price) as total FROM assets")['total'] ?? 0;
        $stats['total_current'] = $db->fetch("SELECT SUM(current_value) FROM assets WHERE status = 'active'")['total'] ?? 0;
        $stats['depreciation'] = $stats['total_purchase'] - $stats['total_current'];
        $stats['maintenance_cost'] = $db->fetch("SELECT SUM(cost) as total FROM maintenance_records WHERE status = 'completed'")['total'] ?? 0;
        break;

    case 'maintenance':
        $stats['data'] = $db->fetchAll(
            "SELECT a.asset_code, a.name, COUNT(mr.id) as maintenance_count, 
                    SUM(mr.cost) as total_cost, MAX(mr.maintenance_date) as last_maintenance
             FROM assets a
             LEFT JOIN maintenance_records mr ON a.id = mr.asset_id
             WHERE a.status = 'active'
             GROUP BY a.id, a.asset_code, a.name
             HAVING maintenance_count > 0
             ORDER BY maintenance_count DESC"
        );
        break;

    default: // overview
        $stats['total_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets")['count'] ?? 0;
        $stats['active_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE status = 'active'")['count'] ?? 0;
        $stats['maintenance_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE status = 'maintenance'")['count'] ?? 0;
        $stats['total_value'] = $db->fetch("SELECT SUM(current_value) FROM assets WHERE status = 'active'")['total'] ?? 0;
        $stats['expiring_warranty'] = $db->fetch(
            "SELECT COUNT(*) as count FROM assets 
             WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
             AND status = 'active'"
        )['count'] ?? 0;
        $stats['upcoming_maintenance'] = $db->fetch(
            "SELECT COUNT(*) as count FROM assets 
             WHERE next_maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
             AND status = 'active'"
        )['count'] ?? 0;
        break;
}

// Get categories for filter
$categories = $db->fetchAll("SELECT id, name FROM asset_categories WHERE status = 'active' ORDER BY name");
?>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> التقارير</h5>
        </div>
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?type=overview" class="btn btn-<?php echo $reportType == 'overview' ? 'primary' : 'outline-primary'; ?>">
                    نظرة عامة
                </a>
                <a href="?type=by_category" class="btn btn-<?php echo $reportType == 'by_category' ? 'primary' : 'outline-primary'; ?>">
                    حسب التصنيف
                </a>
                <a href="?type=by_location" class="btn btn-<?php echo $reportType == 'by_location' ? 'primary' : 'outline-primary'; ?>">
                    حسب الموقع
                </a>
                <a href="?type=by_status" class="btn btn-<?php echo $reportType == 'by_status' ? 'primary' : 'outline-primary'; ?>">
                    حسب الحالة
                </a>
                <a href="?type=financial" class="btn btn-<?php echo $reportType == 'financial' ? 'primary' : 'outline-primary'; ?>">
                    مالي
                </a>
                <a href="?type=maintenance" class="btn btn-<?php echo $reportType == 'maintenance' ? 'primary' : 'outline-primary'; ?>">
                    الصيانة
                </a>
            </div>
        </div>
    </div>

    <?php if ($reportType == 'overview'): ?>
        <!-- Overview Report -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">إجمالي الأصول</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_assets']); ?></h2>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">أصول نشطة</h6>
                            <h2 class="mb-0 text-success"><?php echo number_format($stats['active_assets']); ?></h2>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">القيمة الإجمالية</h6>
                            <h2 class="mb-0 text-info"><?php echo formatCurrency($stats['total_value']); ?></h2>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">ضمان منتهٍ قريباً</h6>
                            <h2 class="mb-0 text-warning"><?php echo number_format($stats['expiring_warranty']); ?></h2>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($reportType == 'financial'): ?>
        <!-- Financial Report -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">التقرير المالي</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted">إجمالي قيمة الشراء</h6>
                                <h3 class="text-primary"><?php echo formatCurrency($stats['total_purchase']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted">القيمة الحالية</h6>
                                <h3 class="text-success"><?php echo formatCurrency($stats['total_current']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted">إجمالي الإهلاك</h6>
                                <h3 class="text-danger"><?php echo formatCurrency($stats['depreciation']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted">تكلفة الصيانة</h6>
                                <h3 class="text-warning"><?php echo formatCurrency($stats['maintenance_cost']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif (isset($stats['data'])): ?>
        <!-- Data Table Reports -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php
                    $titles = [
                        'by_category' => 'الأصول حسب التصنيف',
                        'by_location' => 'الأصول حسب الموقع',
                        'by_status' => 'الأصول حسب الحالة',
                        'maintenance' => 'سجل الصيانة'
                    ];
                    echo $titles[$reportType] ?? 'التقرير';
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <?php if ($reportType == 'by_category'): ?>
                                    <th>التصنيف</th>
                                    <th>النوع</th>
                                    <th>عدد الأصول</th>
                                    <th>القيمة الإجمالية</th>
                                <?php elseif ($reportType == 'by_location'): ?>
                                    <th>الموقع</th>
                                    <th>النوع</th>
                                    <th>عدد الأصول</th>
                                    <th>القيمة الإجمالية</th>
                                <?php elseif ($reportType == 'by_status'): ?>
                                    <th>الحالة</th>
                                    <th>عدد الأصول</th>
                                    <th>القيمة الإجمالية</th>
                                <?php elseif ($reportType == 'maintenance'): ?>
                                    <th>كود الأصل</th>
                                    <th>الاسم</th>
                                    <th>عدد عمليات الصيانة</th>
                                    <th>إجمالي التكلفة</th>
                                    <th>آخر صيانة</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['data'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">لا توجد بيانات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stats['data'] as $row): ?>
                                    <tr>
                                        <?php if ($reportType == 'by_category'): ?>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                            <td><?php echo number_format($row['count']); ?></td>
                                            <td><?php echo formatCurrency($row['total_value'] ?? 0); ?></td>
                                        <?php elseif ($reportType == 'by_location'): ?>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                            <td><?php echo number_format($row['count']); ?></td>
                                            <td><?php echo formatCurrency($row['total_value'] ?? 0); ?></td>
                                        <?php elseif ($reportType == 'by_status'): ?>
                                            <td><?php echo formatStatus($row['status']); ?></td>
                                            <td><?php echo number_format($row['count']); ?></td>
                                            <td><?php echo formatCurrency($row['total_value'] ?? 0); ?></td>
                                        <?php elseif ($reportType == 'maintenance'): ?>
                                            <td><?php echo htmlspecialchars($row['asset_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo number_format($row['maintenance_count']); ?></td>
                                            <td><?php echo formatCurrency($row['total_cost'] ?? 0); ?></td>
                                            <td><?php echo $row['last_maintenance'] ? formatDate($row['last_maintenance']) : '-'; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>