<?php
$pageTitle = 'تفاصيل الموقع';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

$location = $db->fetch(
    "SELECT l.*, 
     d.name as department_name, d.code as department_code,
     f.name as faculty_name, f.code as faculty_code,
     c.name as campus_name, c.code as campus_code
     FROM locations l
     LEFT JOIN departments d ON l.department_id = d.id
     LEFT JOIN faculties f ON d.faculty_id = f.id
     LEFT JOIN campuses c ON f.campus_id = c.id
     WHERE l.id = ?",
    [$id]
);

if (!$location) {
    setAlert('الموقع غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

// Get assets in this location
$assets = $db->fetchAll(
    "SELECT a.*, ac.name as category_name 
     FROM assets a
     LEFT JOIN asset_categories ac ON a.category_id = ac.id
     WHERE a.location_id = ?
     ORDER BY a.name",
    [$id]
);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> تفاصيل الموقع</h5>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/modules/locations/edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> تعديل
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>اسم الموقع:</strong><br>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>الحالة:</strong><br>
                            <?php echo formatStatus($location['status']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>النوع:</strong><br>
                            <span class="badge bg-info"><?php echo htmlspecialchars($location['type']); ?></span>
                        </div>
                        <?php if (!empty($location['code'])): ?>
                            <div class="col-md-6">
                                <strong>الكود:</strong><br>
                                <?php echo htmlspecialchars($location['code']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-12">
                            <strong>الموقع الكامل:</strong><br>
                            <?php
                            $locationParts = array_filter([
                                $location['campus_name'],
                                $location['faculty_name'],
                                $location['department_name'],
                                $location['name']
                            ]);
                            echo htmlspecialchars(implode(' - ', $locationParts));
                            ?>
                        </div>

                        <?php if (!empty($location['building'])): ?>
                            <div class="col-md-4">
                                <strong>المبنى:</strong><br>
                                <?php echo htmlspecialchars($location['building']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location['floor'])): ?>
                            <div class="col-md-4">
                                <strong>الطابق:</strong><br>
                                <?php echo $location['floor']; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location['room_number'])): ?>
                            <div class="col-md-4">
                                <strong>رقم الغرفة:</strong><br>
                                <?php echo htmlspecialchars($location['room_number']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location['capacity'])): ?>
                            <div class="col-md-6">
                                <strong>السعة:</strong><br>
                                <?php echo $location['capacity']; ?> شخص
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location['responsible_person'])): ?>
                            <div class="col-md-6">
                                <strong>الشخص المسؤول:</strong><br>
                                <?php echo htmlspecialchars($location['responsible_person']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Assets in Location -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> الأصول في هذا الموقع (<?php echo count($assets); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($assets)): ?>
                        <p class="text-muted text-center">لا توجد أصول في هذا الموقع</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الكود</th>
                                        <th>الاسم</th>
                                        <th>التصنيف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assets as $asset): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asset['asset_code']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['category_name'] ?? '-'); ?></td>
                                            <td><?php echo formatStatus($asset['status']); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/modules/assets/view.php?id=<?php echo $asset['id']; ?>"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>