<?php
$pageTitle = 'تفاصيل الأصل';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/assets/index.php');
    exit;
}

$asset = $db->fetch(
    "SELECT a.*, 
     ac.name as category_name, ac.type as category_type,
     l.name as location_name, l.type as location_type,
     d.name as department_name,
     f.name as faculty_name,
     c.name as campus_name,
     u.full_name as assigned_to_name, u.username as assigned_to_username,
     creator.full_name as created_by_name
     FROM assets a
     LEFT JOIN asset_categories ac ON a.category_id = ac.id
     LEFT JOIN locations l ON a.location_id = l.id
     LEFT JOIN departments d ON a.department_id = d.id
     LEFT JOIN faculties f ON a.faculty_id = f.id
     LEFT JOIN campuses c ON a.campus_id = c.id
     LEFT JOIN users u ON a.assigned_to_user_id = u.id
     LEFT JOIN users creator ON a.created_by = creator.id
     WHERE a.id = ?",
    [$id]
);

if (!$asset) {
    setAlert('الأصل غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/assets/index.php');
    exit;
}

// Get maintenance history
$maintenanceHistory = $db->fetchAll(
    "SELECT mr.*, u.full_name as performed_by_name 
     FROM maintenance_records mr 
     LEFT JOIN users u ON mr.performed_by = u.id 
     WHERE mr.asset_id = ? 
     ORDER BY mr.maintenance_date DESC 
     LIMIT 10",
    [$id]
);

// Get transfer history
$transferHistory = $db->fetchAll(
    "SELECT at.*, 
     fl.name as from_location_name, tl.name as to_location_name,
     u.full_name as transferred_by_name
     FROM asset_transfers at
     LEFT JOIN locations fl ON at.from_location_id = fl.id
     LEFT JOIN locations tl ON at.to_location_id = tl.id
     LEFT JOIN users u ON at.transferred_by = u.id
     WHERE at.asset_id = ? 
     ORDER BY at.transfer_date DESC 
     LIMIT 10",
    [$id]
);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> تفاصيل الأصل</h5>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/modules/assets/edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> تعديل
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>كود الأصل:</strong><br>
                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($asset['asset_code']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>الحالة:</strong><br>
                            <?php echo formatStatus($asset['status']); ?>
                        </div>
                        <div class="col-md-12">
                            <strong>الاسم:</strong><br>
                            <?php echo htmlspecialchars($asset['name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>التصنيف:</strong><br>
                            <?php echo htmlspecialchars($asset['category_name'] ?? '-'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>الوصف:</strong><br>
                            <?php echo htmlspecialchars($asset['description'] ?? '-'); ?>
                        </div>

                        <?php if (!empty($asset['brand']) || !empty($asset['model'])): ?>
                            <div class="col-md-6">
                                <strong>العلامة التجارية / الموديل:</strong><br>
                                <?php echo htmlspecialchars(trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''))); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($asset['serial_number'])): ?>
                            <div class="col-md-6">
                                <strong>الرقم التسلسلي:</strong><br>
                                <?php echo htmlspecialchars($asset['serial_number']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-12">
                            <strong>الموقع:</strong><br>
                            <?php
                            $locationParts = array_filter([
                                $asset['location_name'] ? $asset['location_name'] . ' (' . $asset['location_type'] . ')' : null,
                                $asset['department_name'],
                                $asset['faculty_name'],
                                $asset['campus_name']
                            ]);
                            echo htmlspecialchars(implode(' - ', $locationParts) ?: 'غير محدد');
                            ?>
                        </div>

                        <?php if (!empty($asset['assigned_to_name'])): ?>
                            <div class="col-md-6">
                                <strong>معين إلى:</strong><br>
                                <?php echo htmlspecialchars($asset['assigned_to_name']); ?>
                                <?php if (!empty($asset['assigned_date'])): ?>
                                    <br><small class="text-muted">في <?php echo formatDate($asset['assigned_date']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-4">
                            <strong>سعر الشراء:</strong><br>
                            <?php echo formatCurrency($asset['purchase_price']); ?>
                            <?php if (!empty($asset['purchase_date'])): ?>
                                <br><small class="text-muted">في <?php echo formatDate($asset['purchase_date']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>القيمة الحالية:</strong><br>
                            <span class="text-success fw-bold"><?php echo formatCurrency($asset['current_value']); ?></span>
                        </div>
                        <?php if ($asset['depreciation_rate'] > 0): ?>
                            <div class="col-md-4">
                                <strong>معدل الإهلاك:</strong><br>
                                <?php echo number_format($asset['depreciation_rate'], 2); ?>%
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($asset['warranty_end_date'])): ?>
                            <div class="col-12">
                                <hr>
                            </div>
                            <div class="col-md-6">
                                <strong>الضمان:</strong><br>
                                <?php if ($asset['warranty_start_date']): ?>
                                    من <?php echo formatDate($asset['warranty_start_date']); ?>
                                <?php endif; ?>
                                <?php if ($asset['warranty_end_date']): ?>
                                    إلى <?php
                                        $warrantyEnd = strtotime($asset['warranty_end_date']);
                                        $daysLeft = floor(($warrantyEnd - time()) / 86400);
                                        echo formatDate($asset['warranty_end_date']);
                                        if ($daysLeft > 0 && $daysLeft <= 30) {
                                            echo ' <span class="badge bg-warning">ينتهي خلال ' . $daysLeft . ' يوم</span>';
                                        } elseif ($daysLeft <= 0) {
                                            echo ' <span class="badge bg-danger">منتهي</span>';
                                        }
                                        ?>
                                <?php endif; ?>
                                <?php if (!empty($asset['warranty_provider'])): ?>
                                    <br><small class="text-muted">مقدم الضمان: <?php echo htmlspecialchars($asset['warranty_provider']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($asset['next_maintenance_date'])): ?>
                            <div class="col-md-6">
                                <strong>الصيانة القادمة:</strong><br>
                                <?php
                                $maintenanceDate = strtotime($asset['next_maintenance_date']);
                                $daysLeft = floor(($maintenanceDate - time()) / 86400);
                                echo formatDate($asset['next_maintenance_date']);
                                if ($daysLeft <= 7 && $daysLeft > 0) {
                                    echo ' <span class="badge bg-warning">خلال ' . $daysLeft . ' يوم</span>';
                                } elseif ($daysLeft <= 0) {
                                    echo ' <span class="badge bg-danger">متأخر</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($asset['notes'])): ?>
                            <div class="col-12">
                                <hr>
                            </div>
                            <div class="col-12">
                                <strong>ملاحظات:</strong><br>
                                <?php echo nl2br(htmlspecialchars($asset['notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <hr>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>تم الإنشاء:</strong> <?php echo formatDate($asset['created_at'], 'Y-m-d H:i'); ?>
                                بواسطة <?php echo htmlspecialchars($asset['created_by_name'] ?? '-'); ?>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>آخر تحديث:</strong> <?php echo formatDate($asset['updated_at'], 'Y-m-d H:i'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance History -->
            <?php if (!empty($maintenanceHistory)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tools"></i> سجل الصيانة</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>النوع</th>
                                        <th>التكلفة</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenanceHistory as $maint): ?>
                                        <tr>
                                            <td><?php echo formatDate($maint['maintenance_date']); ?></td>
                                            <td><?php echo htmlspecialchars($maint['maintenance_type']); ?></td>
                                            <td><?php echo formatCurrency($maint['cost']); ?></td>
                                            <td><?php echo formatStatus($maint['status']); ?></td>
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
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> إجراءات سريعة</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/modules/maintenance/add.php?asset_id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-tools"></i> تسجيل صيانة
                        </a>
                        <a href="<?php echo SITE_URL; ?>/modules/transfers/add.php?asset_id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="bi bi-arrow-left-right"></i> نقل الأصل
                        </a>
                        <a href="<?php echo SITE_URL; ?>/modules/transfers/loan.php?asset_id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="bi bi-hand-thumbs-up"></i> إعارة الأصل
                        </a>
                        <?php if (!empty($asset['qr_code'])): ?>
                            <button type="button" class="btn btn-success" onclick="printQRCode()">
                                <i class="bi bi-qr-code"></i> طباعة QR Code
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printQRCode() {
        // QR Code printing functionality would go here
        alert('سيتم طباعة QR Code');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>