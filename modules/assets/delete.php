<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('admin');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/assets/index.php');
    exit;
}

$asset = $db->fetch("SELECT asset_code FROM assets WHERE id = ?", [$id]);

if (!$asset) {
    setAlert('الأصل غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/assets/index.php');
    exit;
}

// Delete asset (cascade will handle related records)
if ($db->query("DELETE FROM assets WHERE id = ?", [$id])) {
    // Log activity
    $db->query(
        "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
        [$_SESSION['user_id'], 'delete', 'asset', $id, "Deleted asset: " . $asset['asset_code']]
    );

    setAlert('تم حذف الأصل بنجاح', 'success');
} else {
    setAlert('حدث خطأ أثناء حذف الأصل', 'danger');
}

header('Location: ' . SITE_URL . '/modules/assets/index.php');
exit;
