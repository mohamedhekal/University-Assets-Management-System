<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    $db = getDB();
    $db->query(
        "INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address) VALUES (?, ?, ?, ?, ?)",
        [
            $_SESSION['user_id'],
            'logout',
            'user',
            'User logged out',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]
    );
}

session_destroy();
header('Location: ' . SITE_URL . '/login.php');
exit;
