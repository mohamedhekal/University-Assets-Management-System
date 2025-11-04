<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $db = getDB();
        $user = $db->fetch(
            "SELECT id, username, email, password, full_name, role, status FROM users WHERE username = ? OR email = ?",
            [$username, $username]
        );

        if ($user && verifyPassword($password, $user['password'])) {
            if ($user['status'] === 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Log activity
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address) VALUES (?, ?, ?, ?, ?)",
                    [
                        $user['id'],
                        'login',
                        'user',
                        'User logged in',
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]
                );

                header('Location: ' . SITE_URL . '/index.php');
                exit;
            } else {
                $error = 'حسابك غير مفعل. يرجى التواصل مع الإدارة';
            }
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}

$pageTitle = 'تسجيل الدخول';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .login-footer small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
            display: block;
            line-height: 1.6;
        }

        .login-footer small strong {
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
        }

        .login-footer .footer-subtitle {
            display: block;
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 3px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-building"></i>
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="text-muted">نظام إدارة الأصول الجامعية</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> تسجيل الدخول
                </button>
            </div>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">افتراضي: admin / admin123</small>
        </div>
    </div>

    <!-- Footer -->
    <footer class="login-footer">
        <small>
            Task Created by <strong>Mohamed Hekal</strong>
            <span class="footer-subtitle">Task for Interview Purposes</span>
        </small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>