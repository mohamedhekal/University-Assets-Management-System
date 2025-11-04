<?php
$pageTitle = 'إدارة المستخدمين';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
requirePermission('admin');

$db = getDB();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$role = sanitizeInput($_GET['role'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($role)) {
    $where[] = "u.role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    $where[] = "u.status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
$total = $db->fetch($countSql, $params)['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Get users
$sql = "SELECT u.*, 
        f.name as faculty_name,
        d.name as department_name,
        c.name as campus_name
        FROM users u
        LEFT JOIN faculties f ON u.faculty_id = f.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN campuses c ON f.campus_id = c.id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$users = $db->fetchAll($sql, $params);

// Statistics
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'active' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")['count'] ?? 0,
    'admins' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'] ?? 0,
];

// Role badges
function getRoleBadge($role)
{
    $badges = [
        'admin' => 'danger',
        'faculty_manager' => 'primary',
        'lab_manager' => 'info',
        'staff' => 'success',
        'guest' => 'secondary'
    ];
    $badge = $badges[$role] ?? 'secondary';
    $roleNames = [
        'admin' => 'مدير',
        'faculty_manager' => 'مدير كلية',
        'lab_manager' => 'مدير مختبر',
        'staff' => 'موظف',
        'guest' => 'زائر'
    ];
    $roleName = $roleNames[$role] ?? $role;
    return '<span class="badge bg-' . $badge . '">' . $roleName . '</span>';
}
?>

<div class="container-fluid">
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">إجمالي المستخدمين</h6>
                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">المستخدمون النشطون</h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['active']; ?></h3>
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
                        <h6 class="text-muted mb-1">غير النشط</h6>
                        <h3 class="mb-0 text-secondary"><?php echo $stats['inactive']; ?></h3>
                    </div>
                    <div class="stat-icon bg-secondary">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">المديرون</h6>
                        <h3 class="mb-0 text-danger"><?php echo $stats['admins']; ?></h3>
                    </div>
                    <div class="stat-icon bg-danger">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people"></i> قائمة المستخدمين</h5>
            <a href="<?php echo SITE_URL; ?>/modules/users/add.php" class="btn btn-light">
                <i class="bi bi-plus-circle"></i> إضافة مستخدم جديد
            </a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="بحث (اسم المستخدم، الاسم، البريد الإلكتروني)..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">جميع الأدوار</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>مدير</option>
                        <option value="faculty_manager" <?php echo $role === 'faculty_manager' ? 'selected' : ''; ?>>مدير كلية</option>
                        <option value="lab_manager" <?php echo $role === 'lab_manager' ? 'selected' : ''; ?>>مدير مختبر</option>
                        <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>موظف</option>
                        <option value="guest" <?php echo $role === 'guest' ? 'selected' : ''; ?>>زائر</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> بحث
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo SITE_URL; ?>/modules/users/index.php" class="btn btn-secondary w-100">إعادة تعيين</a>
                </div>
            </form>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>الكلية/القسم</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا يوجد مستخدمون</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo getRoleBadge($user['role']); ?></td>
                                    <td>
                                        <?php if ($user['faculty_name']): ?>
                                            <?php echo htmlspecialchars($user['faculty_name']); ?>
                                            <?php if ($user['department_name']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($user['department_name']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatStatus($user['status']); ?></td>
                                    <td><?php echo formatDate($user['created_at'], 'Y-m-d'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>"
                                                class="btn btn-info" title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>"
                                                class="btn btn-warning" title="تعديل">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="<?php echo SITE_URL; ?>/modules/users/delete.php?id=<?php echo $user['id']; ?>"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')"
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
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">السابق</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">التالي</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>