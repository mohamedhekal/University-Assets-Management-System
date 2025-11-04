<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>

    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            position: fixed;
            width: 250px;
            top: 0;
            right: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 1rem 1.5rem;
            display: block;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            padding-right: 2rem;
        }

        .sidebar-menu i {
            width: 25px;
            margin-left: 10px;
        }

        .main-content {
            margin-right: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <?php if (isLoggedIn()): ?>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h5 class="mb-0">
                    <i class="bi bi-building"></i> <?php echo SITE_NAME; ?>
                </h5>
                <small class="text-light"><?php echo $_SESSION['full_name'] ?? 'User'; ?></small>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i> لوحة التحكم
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/modules/assets/index.php" class="<?php echo strpos($currentPage, 'assets') !== false ? 'active' : ''; ?>">
                        <i class="bi bi-box-seam"></i> الأصول
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/modules/locations/index.php" class="<?php echo strpos($currentPage, 'locations') !== false ? 'active' : ''; ?>">
                        <i class="bi bi-geo-alt"></i> المواقع
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/modules/maintenance/index.php" class="<?php echo strpos($currentPage, 'maintenance') !== false ? 'active' : ''; ?>">
                        <i class="bi bi-tools"></i> الصيانة
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/modules/transfers/index.php" class="<?php echo strpos($currentPage, 'transfers') !== false ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-left-right"></i> النقل والإعارة
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/modules/reports/index.php" class="<?php echo strpos($currentPage, 'reports') !== false ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-text"></i> التقارير
                    </a>
                </li>
                <?php if (hasPermission('admin')): ?>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/modules/users/index.php" class="<?php echo strpos($currentPage, 'users') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> المستخدمين
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/modules/settings/index.php" class="<?php echo strpos($currentPage, 'settings') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i> الإعدادات
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/logout.php">
                        <i class="bi bi-box-arrow-left"></i> تسجيل الخروج
                    </a>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary d-md-none" type="button" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="navbar-brand mb-0 h1"><?php echo $pageTitle; ?></span>
                    <div class="ms-auto">
                        <span class="text-muted">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name'] ?? 'User'; ?>
                            <span class="badge bg-primary"><?php echo ucfirst(getUserRole()); ?></span>
                        </span>
                    </div>
                </div>
            </nav>
        <?php endif; ?>