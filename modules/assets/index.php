<?php
$pageTitle = 'إدارة الأصول';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
requirePermission('staff');

$db = getDB();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$status = sanitizeInput($_GET['status'] ?? '');
$location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(a.name LIKE ? OR a.asset_code LIKE ? OR a.serial_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($category_id > 0) {
    $where[] = "a.category_id = ?";
    $params[] = $category_id;
}

if (!empty($status)) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

if ($location_id > 0) {
    $where[] = "a.location_id = ?";
    $params[] = $location_id;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM assets a $whereClause";
$total = $db->fetch($countSql, $params)['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Get assets
$sql = "SELECT a.*, 
        ac.name as category_name, 
        ac.type as category_type,
        l.name as location_name,
        d.name as department_name,
        f.name as faculty_name,
        c.name as campus_name,
        u.full_name as assigned_to_name
        FROM assets a
        LEFT JOIN asset_categories ac ON a.category_id = ac.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN departments d ON a.department_id = d.id
        LEFT JOIN faculties f ON a.faculty_id = f.id
        LEFT JOIN campuses c ON a.campus_id = c.id
        LEFT JOIN users u ON a.assigned_to_user_id = u.id
        $whereClause
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$assets = $db->fetchAll($sql, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT id, name FROM asset_categories WHERE status = 'active' ORDER BY name");

// Get locations for filter
$locations = $db->fetchAll("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name");
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-box-seam"></i> قائمة الأصول</h5>
            <a href="<?php echo SITE_URL; ?>/modules/assets/add.php" class="btn btn-light">
                <i class="bi bi-plus-circle"></i> إضافة أصل جديد
            </a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-select">
                        <option value="0">جميع التصنيفات</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                        <option value="maintenance" <?php echo $status == 'maintenance' ? 'selected' : ''; ?>>صيانة</option>
                        <option value="damaged" <?php echo $status == 'damaged' ? 'selected' : ''; ?>>تالف</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="location_id" class="form-select">
                        <option value="0">جميع المواقع</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>" <?php echo $location_id == $loc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> بحث</button>
                    <a href="<?php echo SITE_URL; ?>/modules/assets/index.php" class="btn btn-secondary">إعادة تعيين</a>
                </div>
            </form>

            <!-- Assets Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>التصنيف</th>
                            <th>الموقع</th>
                            <th>القيمة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد أصول</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($asset['asset_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['category_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $locationInfo = [];
                                        if (!empty($asset['location_name'])) $locationInfo[] = $asset['location_name'];
                                        if (!empty($asset['department_name'])) $locationInfo[] = $asset['department_name'];
                                        if (!empty($asset['faculty_name'])) $locationInfo[] = $asset['faculty_name'];
                                        echo htmlspecialchars(implode(' - ', $locationInfo) ?: '-');
                                        ?>
                                    </td>
                                    <td><?php echo formatCurrency($asset['current_value']); ?></td>
                                    <td><?php echo formatStatus($asset['status']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>/modules/assets/view.php?id=<?php echo $asset['id']; ?>"
                                                class="btn btn-info" title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/modules/assets/edit.php?id=<?php echo $asset['id']; ?>"
                                                class="btn btn-warning" title="تعديل">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (hasPermission('admin')): ?>
                                                <a href="<?php echo SITE_URL; ?>/modules/assets/delete.php?id=<?php echo $asset['id']; ?>"
                                                    class="btn btn-danger"
                                                    onclick="return confirmDelete('هل أنت متأكد من حذف هذا الأصل؟')"
                                                    title="حذف">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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