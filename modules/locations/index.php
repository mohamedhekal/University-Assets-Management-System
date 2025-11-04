<?php
$pageTitle = 'إدارة المواقع';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();

// Get all locations with hierarchy
$locations = $db->fetchAll(
    "SELECT l.*, 
     d.name as department_name, d.code as department_code,
     f.name as faculty_name, f.code as faculty_code,
     c.name as campus_name, c.code as campus_code
     FROM locations l
     LEFT JOIN departments d ON l.department_id = d.id
     LEFT JOIN faculties f ON d.faculty_id = f.id
     LEFT JOIN campuses c ON f.campus_id = c.id
     ORDER BY c.name, f.name, d.name, l.name"
);

// Count assets per location
$locationAssets = [];
foreach ($locations as $loc) {
    $count = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE location_id = ?", [$loc['id']])['count'] ?? 0;
    $locationAssets[$loc['id']] = $count;
}

// Get campuses
$campuses = $db->fetchAll("SELECT * FROM campuses WHERE status = 'active' ORDER BY name");

// Get faculties
$faculties = $db->fetchAll("SELECT * FROM faculties WHERE status = 'active' ORDER BY name");

// Get departments
$departments = $db->fetchAll("SELECT * FROM departments WHERE status = 'active' ORDER BY name");
?>

<div class="container-fluid">
    <div class="row">
        <!-- Locations List -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> قائمة المواقع</h5>
                    <a href="<?php echo SITE_URL; ?>/modules/locations/add.php" class="btn btn-light">
                        <i class="bi bi-plus-circle"></i> إضافة موقع
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>الموقع</th>
                                    <th>النوع</th>
                                    <th>القسم</th>
                                    <th>الكلية</th>
                                    <th>الحرم</th>
                                    <th>عدد الأصول</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($locations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">لا توجد مواقع</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($loc['name']); ?></strong>
                                                <?php if (!empty($loc['code'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($loc['code']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($loc['type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($loc['department_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($loc['faculty_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($loc['campus_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $locationAssets[$loc['id']] ?? 0; ?></span>
                                            </td>
                                            <td><?php echo formatStatus($loc['status']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo SITE_URL; ?>/modules/locations/view.php?id=<?php echo $loc['id']; ?>"
                                                        class="btn btn-info" title="عرض">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/modules/locations/edit.php?id=<?php echo $loc['id']; ?>"
                                                        class="btn btn-warning" title="تعديل">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if (hasPermission('admin')): ?>
                                                        <a href="<?php echo SITE_URL; ?>/modules/locations/delete.php?id=<?php echo $loc['id']; ?>"
                                                            class="btn btn-danger"
                                                            onclick="return confirmDelete()"
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
                </div>
            </div>
        </div>

        <!-- Hierarchy Tree -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> الهيكل التنظيمي</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($campuses as $campus): ?>
                        <div class="mb-3">
                            <h6 class="text-primary">
                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($campus['name']); ?>
                            </h6>
                            <?php
                            $campusFaculties = array_filter($faculties, function ($f) use ($campus) {
                                return $f['campus_id'] == $campus['id'];
                            });
                            ?>
                            <?php foreach ($campusFaculties as $faculty): ?>
                                <div class="ms-3 mb-2">
                                    <strong class="text-success">
                                        <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($faculty['name']); ?>
                                    </strong>
                                    <?php
                                    $facultyDepartments = array_filter($departments, function ($d) use ($faculty) {
                                        return $d['faculty_id'] == $faculty['id'];
                                    });
                                    ?>
                                    <?php foreach ($facultyDepartments as $dept): ?>
                                        <div class="ms-3 mb-1">
                                            <small class="text-info">
                                                <i class="bi bi-folder"></i> <?php echo htmlspecialchars($dept['name']); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>