<?php
$pageTitle = 'تفاصيل المستخدم';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
requirePermission('admin');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

$user = $db->fetch(
    "SELECT u.*, 
     f.name as faculty_name, f.code as faculty_code,
     d.name as department_name, d.code as department_code,
     c.name as campus_name, c.code as campus_code
     FROM users u
     LEFT JOIN faculties f ON u.faculty_id = f.id
     LEFT JOIN departments d ON u.department_id = d.id
     LEFT JOIN campuses c ON f.campus_id = c.id
     WHERE u.id = ?",
    [$id]
);

if (!$user) {
    setAlert('المستخدم غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

// Get role badge
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

// Get user statistics
$userStats = [
    'assets_count' => $db->fetch("SELECT COUNT(*) as count FROM assets WHERE assigned_to_user_id = ?", [$id])['count'] ?? 0,
    'created_assets' => $db->fetch("SELECT COUNT(*) as count FROM assets WHERE created_by = ?", [$id])['count'] ?? 0,
    'maintenance_records' => $db->fetch("SELECT COUNT(*) as count FROM maintenance_records WHERE performed_by = ?", [$id])['count'] ?? 0,
    'transfers' => $db->fetch("SELECT COUNT(*) as count FROM asset_transfers WHERE transferred_by = ?", [$id])['count'] ?? 0,
];

// Get recent activity
$recentActivity = $db->fetchAll(
    "SELECT * FROM activity_logs 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 10",
    [$id]
);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> تفاصيل المستخدم</h5>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/modules/users/edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> تعديل
                        </a>
                        <a href="<?php echo SITE_URL; ?>/modules/users/index.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-right"></i> العودة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>اسم المستخدم:</strong><br>
                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>الحالة:</strong><br>
                            <?php echo formatStatus($user['status']); ?>
                        </div>
                        <div class="col-md-12">
                            <strong>الاسم الكامل:</strong><br>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>البريد الإلكتروني:</strong><br>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>الدور:</strong><br>
                            <?php echo getRoleBadge($user['role']); ?>
                        </div>

                        <?php if (!empty($user['phone'])): ?>
                            <div class="col-md-6">
                                <strong>الهاتف:</strong><br>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-12">
                            <strong>الموقع التنظيمي:</strong><br>
                            <?php
                            $locationParts = array_filter([
                                $user['campus_name'],
                                $user['faculty_name'],
                                $user['department_name']
                            ]);
                            echo htmlspecialchars(implode(' - ', $locationParts) ?: 'غير محدد');
                            ?>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-6">
                            <strong>تاريخ الإنشاء:</strong><br>
                            <?php echo formatDate($user['created_at'], 'Y-m-d H:i'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>آخر تحديث:</strong><br>
                            <?php echo formatDate($user['updated_at'], 'Y-m-d H:i'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <?php if (!empty($recentActivity)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> النشاط الأخير</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الإجراء</th>
                                        <th>النوع</th>
                                        <th>الوصف</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <tr>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($activity['action']); ?></span></td>
                                            <td><?php echo htmlspecialchars($activity['entity_type']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                                            <td><?php echo formatDate($activity['created_at'], 'Y-m-d H:i'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> الإحصائيات</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>الأصول المعينة</span>
                            <span class="badge bg-primary"><?php echo $userStats['assets_count']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>الأصول المنشأة</span>
                            <span class="badge bg-success"><?php echo $userStats['created_assets']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>سجلات الصيانة</span>
                            <span class="badge bg-warning"><?php echo $userStats['maintenance_records']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>عمليات النقل</span>
                            <span class="badge bg-info"><?php echo $userStats['transfers']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> إجراءات سريعة</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/modules/users/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> تعديل المستخدم
                        </a>
                        <?php if ($id != $_SESSION['user_id']): ?>
                            <a href="<?php echo SITE_URL; ?>/modules/users/delete.php?id=<?php echo $id; ?>"
                                class="btn btn-danger"
                                onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                <i class="bi bi-trash"></i> حذف المستخدم
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/modules/users/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-right"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>