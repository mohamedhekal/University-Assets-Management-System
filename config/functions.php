<?php

/**
 * Global Helper Functions
 */

// Security Functions
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken()
{
    return bin2hex(random_bytes(32));
}

function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Authentication Functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function getUserRole()
{
    return $_SESSION['user_role'] ?? 'guest';
}

function hasPermission($requiredRole)
{
    $roles = ['guest' => 0, 'staff' => 1, 'lab_manager' => 2, 'faculty_manager' => 3, 'admin' => 4];
    $userRole = getUserRole();
    return ($roles[$userRole] ?? 0) >= ($roles[$requiredRole] ?? 0);
}

function requirePermission($requiredRole)
{
    requireLogin();
    if (!hasPermission($requiredRole)) {
        header('Location: ' . SITE_URL . '/index.php?error=access_denied');
        exit;
    }
}

// Format Functions
function formatDate($date, $format = 'Y-m-d')
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

function formatCurrency($amount)
{
    return number_format($amount, 2) . ' EGP';
}

function formatStatus($status)
{
    $badges = [
        'active' => 'success',
        'inactive' => 'secondary',
        'maintenance' => 'warning',
        'damaged' => 'danger',
        'retired' => 'dark'
    ];
    $badge = $badges[$status] ?? 'secondary';
    return '<span class="badge bg-' . $badge . '">' . ucfirst($status) . '</span>';
}

// Alert Functions
function setAlert($message, $type = 'success')
{
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getAlert()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Database Helper
function getDB()
{
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

// Generate QR Code Data
function generateQRCodeData($assetId, $assetCode)
{
    return json_encode([
        'asset_id' => $assetId,
        'asset_code' => $assetCode,
        'timestamp' => time()
    ]);
}
