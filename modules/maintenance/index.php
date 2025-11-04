<?php
$pageTitle = 'إدارة الصيانة';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$type = sanitizeInput($_GET['type'] ?? '');

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(a.name LIKE ? OR a.asset_code LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status)) {
    $where[] = "mr.status = ?";
    $params[] = $status;
}

if (!empty($type)) {
    $where[] = "mr.maintenance_type = ?";
    $params[] = $type;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM maintenance_records mr 
             INNER JOIN assets a ON mr.asset_id = a.id 
             $whereClause";
$total = $db->fetch($countSql, $params)['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Get maintenance records
$sql = "SELECT mr.*, 
        a.asset_code, a.name as asset_name,
        u.full_name as performed_by_name
        FROM maintenance_records mr
        INNER JOIN assets a ON mr.asset_id = a.id
        LEFT JOIN users u ON mr.performed_by = u.id
        $whereClause
        ORDER BY mr.maintenance_date DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$records = $db->fetchAll($sql, $params);

// Statistics
$stats = [
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM maintenance_records WHERE status = 'pending'")['count'] ?? 0,
    'in_progress' => $db->fetch("SELECT COUNT(*) as count FROM maintenance_records WHERE status = 'in_progress'")['count'] ?? 0,
    'completed' => $db->fetch("SELECT COUNT(*) as count FROM maintenance_records WHERE status = 'completed'")['count'] ?? 0,
];
?>

<div class="container-fluid">
    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">قيد الانتظار</h6>
                        <h2 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h2>
                    </div>
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">قيد التنفيذ</h6>
                        <h2 class="mb-0 text-info"><?php echo $stats['in_progress']; ?></h2>
                    </div>
                    <div class="stat-icon bg-info">
                        <i class="bi bi-gear"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">مكتملة</h6>
                        <h2 class="mb-0 text-success"><?php echo $stats['completed']; ?></h2>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-tools"></i> سجل الصيانة</h5>
            <a href="<?php echo SITE_URL; ?>/modules/maintenance/add.php" class="btn btn-light">
                <i class="bi bi-plus-circle"></i> إضافة سجل صيانة
            </a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                        <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>مكتملة</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">جميع الأنواع</option>
                        <option value="preventive" <?php echo $type == 'preventive' ? 'selected' : ''; ?>>وقائية</option>
                        <option value="corrective" <?php echo $type == 'corrective' ? 'selected' : ''; ?>>تصحيحية</option>
                        <option value="upgrade" <?php echo $type == 'upgrade' ? 'selected' : ''; ?>>ترقية</option>
                        <option value="inspection" <?php echo $type == 'inspection' ? 'selected' : ''; ?>>فحص</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> بحث</button>
                </div>
            </form>

            <!-- Records Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الأصل</th>
                            <th>النوع</th>
                            <th>التكلفة</th>
                            <th>مقدم الخدمة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد سجلات صيانة</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['maintenance_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['asset_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($record['asset_name']); ?></small>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($record['maintenance_type']); ?></span></td>
                                    <td><?php echo formatCurrency($record['cost']); ?></td>
                                    <td><?php echo htmlspecialchars($record['service_provider'] ?? '-'); ?></td>
                                    <td><?php echo formatStatus($record['status']); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/modules/maintenance/view.php?id=<?php echo $record['id']; ?>"
                                            class="btn btn-sm btn-info" title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/modules/maintenance/edit.php?id=<?php echo $record['id']; ?>"
                                            class="btn btn-sm btn-warning" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">السابق</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">التالي</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>