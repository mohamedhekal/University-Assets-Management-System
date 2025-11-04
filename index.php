<?php
$pageTitle = 'لوحة التحكم';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Get Statistics
$stats = [];

// Total Assets
$stats['total_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets")['count'] ?? 0;

// Active Assets
$stats['active_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE status = 'active'")['count'] ?? 0;

// Assets Under Maintenance
$stats['maintenance_assets'] = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE status = 'maintenance'")['count'] ?? 0;

// Assets with Expiring Warranty (next 30 days)
$stats['expiring_warranty'] = $db->fetch(
    "SELECT COUNT(*) as count FROM assets WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'"
)['count'] ?? 0;

// Total Value
$stats['total_value'] = $db->fetch("SELECT SUM(current_value) as total FROM assets WHERE status = 'active'")['total'] ?? 0;

// Recent Assets
$recentAssets = $db->fetchAll(
    "SELECT a.*, ac.name as category_name, l.name as location_name 
     FROM assets a 
     LEFT JOIN asset_categories ac ON a.category_id = ac.id 
     LEFT JOIN locations l ON a.location_id = l.id 
     ORDER BY a.created_at DESC 
     LIMIT 10"
);

// Upcoming Maintenance
$upcomingMaintenance = $db->fetchAll(
    "SELECT a.asset_code, a.name, a.next_maintenance_date, l.name as location_name 
     FROM assets a 
     LEFT JOIN locations l ON a.location_id = l.id 
     WHERE a.next_maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
     AND a.status = 'active' 
     ORDER BY a.next_maintenance_date ASC 
     LIMIT 10"
);

// Recent Activities
$recentActivities = $db->fetchAll(
    "SELECT al.*, u.full_name as user_name 
     FROM activity_logs al 
     LEFT JOIN users u ON al.user_id = u.id 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);
?>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
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

        <div class="col-md-3 col-sm-6">
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

        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">تحت الصيانة</h6>
                        <h2 class="mb-0 text-warning"><?php echo number_format($stats['maintenance_assets']); ?></h2>
                    </div>
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
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
    </div>

    <div class="row g-4">
        <!-- Recent Assets -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> الأصول المضافة حديثاً</h5>
                    <a href="<?php echo SITE_URL; ?>/modules/assets/index.php" class="btn btn-sm btn-light">
                        عرض الكل <i class="bi bi-arrow-left"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الاسم</th>
                                    <th>التصنيف</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAssets)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد أصول</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAssets as $asset): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asset['asset_code']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['category_name'] ?? '-'); ?></td>
                                            <td><?php echo formatStatus($asset['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Maintenance -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> الصيانة القادمة</h5>
                    <a href="<?php echo SITE_URL; ?>/modules/maintenance/index.php" class="btn btn-sm btn-light">
                        عرض الكل <i class="bi bi-arrow-left"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الاسم</th>
                                    <th>الموقع</th>
                                    <th>تاريخ الصيانة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingMaintenance)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد صيانة قادمة</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingMaintenance as $maintenance): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($maintenance['asset_code']); ?></td>
                                            <td><?php echo htmlspecialchars($maintenance['name']); ?></td>
                                            <td><?php echo htmlspecialchars($maintenance['location_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?php echo formatDate($maintenance['next_maintenance_date']); ?>
                                                </span>
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>