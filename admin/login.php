<?php
require_once '../config/admin-config.php';
require_once '../config/ensure-admin-field.php';

// Ensure is_admin column exists
$db = getDBConnection();
ensureAdminFieldExists($db);

// If already logged in as admin, redirect to dashboard
if (isAdminLoggedIn() && isAdmin()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if is_admin column exists, if not use old method
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        // New method: check is_admin
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_admin = TRUE");
    } else {
        // Fallback: check role = 'admin' (old method)
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
    }
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Set admin session variables (separate from regular user session)
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_user_email'] = $user['email'];
        $_SESSION['admin_user_role'] = $user['role']; // Keep role for display (user/partner)
        $_SESSION['admin_user_name'] = $user['first_name'] ?? $user['email'];
        
        $redirect = $_SESSION['admin_redirect_after_login'] ?? 'index.php';
        unset($_SESSION['admin_redirect_after_login']);
        redirect($redirect);
    } else {
        $error = 'Неверный email или пароль. Убедитесь, что вы используете учетную запись администратора.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - ДенЛиФорс</title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }

        .admin-login-container {
            width: 100%;
            max-width: 450px;
        }

        .admin-login-box {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .admin-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-login-header img {
            height: 60px;
            width: auto;
            margin-bottom: 1.5rem;
        }

        .admin-login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #2E2216;
        }

        .admin-login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #fcc;
        }

        .admin-login-form .form-group {
            margin-bottom: 1.5rem;
        }

        .admin-login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2E2216;
        }

        .admin-login-form input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .admin-login-form input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .admin-login-footer {
            margin-top: 2rem;
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .admin-login-footer a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .admin-login-footer a:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-login-header">
                <img src="<?php echo ASSETS_PATH; ?>/images/logo.svg" alt="Logo">
                <h1>Вход в админ-панель</h1>
                <p>Используйте учетную запись администратора</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="admin-login-form">
                <div class="form-group">
                    <label>Email администратора</label>
                    <input type="email" name="email" required autofocus placeholder="admin@denlifors.ru">
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required placeholder="Введите пароль">
                </div>
                <button type="submit" class="btn-primary">
                    Войти в админ-панель
                </button>
            </form>
            
            <div class="admin-login-footer">
                <a href="<?php echo BASE_URL; ?>">
                    ← Вернуться на сайт
                </a>
            </div>
        </div>
    </div>
</body>
</html>

