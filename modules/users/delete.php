<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();
requirePermission('admin');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setAlert('معرف غير صحيح', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

// Prevent self-deletion
if ($id == $_SESSION['user_id']) {
    setAlert('لا يمكنك حذف حسابك الخاص', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

$user = $db->fetch("SELECT username FROM users WHERE id = ?", [$id]);

if (!$user) {
    setAlert('المستخدم غير موجود', 'danger');
    header('Location: ' . SITE_URL . '/modules/users/index.php');
    exit;
}

// Check if user has related records that might prevent deletion
// Note: The schema uses ON DELETE SET NULL for most foreign keys,
// but we should check for assets created by this user
$assetsCount = $db->fetch("SELECT COUNT(*) as count FROM assets WHERE created_by = ?", [$id])['count'] ?? 0;

if ($assetsCount > 0) {
    // Instead of deleting, we can deactivate the user
    if ($db->query("UPDATE users SET status = 'inactive' WHERE id = ?", [$id])) {
        // Log activity
        $db->query(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
            [$_SESSION['user_id'], 'update', 'user', $id, "Deactivated user: " . $user['username'] . " (has $assetsCount assets)"]
        );

        setAlert('تم تعطيل المستخدم بنجاح (لا يمكن حذفه لأنه لديه أصول مرتبطة)', 'success');
    } else {
        setAlert('حدث خطأ أثناء تعطيل المستخدم', 'danger');
    }
} else {
    // Safe to delete - delete user
    if ($db->query("DELETE FROM users WHERE id = ?", [$id])) {
        // Log activity
        $db->query(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)",
            [$_SESSION['user_id'], 'delete', 'user', $id, "Deleted user: " . $user['username']]
        );

        setAlert('تم حذف المستخدم بنجاح', 'success');
    } else {
        setAlert('حدث خطأ أثناء حذف المستخدم', 'danger');
    }
}

header('Location: ' . SITE_URL . '/modules/users/index.php');
exit;
