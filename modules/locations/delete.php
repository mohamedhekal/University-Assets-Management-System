<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('admin');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

$location = $db->fetch("SELECT name FROM locations WHERE id = ?", [$id]);

if (!$location) {
    setAlert('الموقع غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

// Check if location has assets
$assetsCount = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE location_id = ?", [$id])['count'] ?? 0;

if ($assetsCount > 0) {
    setAlert('لا يمكن حذف الموقع لأنه يحتوي على أصول. يرجى نقل الأصول أولاً', 'danger');
    header('Location: ' . SITE_URL . '/modules/locations/index.php');
    exit;
}

// Delete location
if ($db->query("DELETE FROM locations WHERE id = ?", [$id])) {
    // Log activity
    $db->query(
        "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
        [$_SESSION['user_id'], 'delete', 'location', $id, "Deleted location: " . $location['name']]
    );

    setAlert('تم حذف الموقع بنجاح', 'success');
} else {
    setAlert('حدث خطأ أثناء حذف الموقع', 'danger');
}

header('Location: ' . SITE_URL . '/modules/locations/index.php');
exit;
