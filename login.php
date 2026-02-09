<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$info = '';
$prefillLogin = sanitize($_GET['reg_id'] ?? '');
if (!empty($_GET['confirmed'])) {
    $info = 'Регистрация подтверждена. Введите ваш ID и пароль для входа.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitize($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $db = getDBConnection();
    // Проверяем, является ли ввод email или ID
    $candidates = [];
    if (is_numeric($login)) {
        // 1) По ID пользователя
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => (int)$login]);
        $u = $stmt->fetch();
        if ($u) {
            $candidates[$u['id']] = $u;
        }

        // 2) По ID заявки партнёра
        $stmt = $db->prepare("SELECT email FROM partner_registrations WHERE id = :id AND status = 'confirmed' LIMIT 1");
        $stmt->execute([':id' => (int)$login]);
        $reg = $stmt->fetch();
        if ($reg && !empty($reg['email'])) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $reg['email']]);
            $u2 = $stmt->fetch();
            if ($u2) {
                $candidates[$u2['id']] = $u2;
            }
        }
    } else {
        // Иначе ищем по email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $login]);
        $u = $stmt->fetch();
        if ($u) {
            $candidates[$u['id']] = $u;
        }
    }

    $user = null;
    foreach ($candidates as $cand) {
        if ($cand && password_verify($password, $cand['password'])) {
            $user = $cand;
            break;
        }
    }
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        
        // Check if is_admin column exists
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            $_SESSION['user_role'] = $user['role']; // user or partner (not admin)
            $_SESSION['user_is_admin'] = $user['is_admin'] ?? false; // Separate admin flag
        } else {
            // Fallback: old method
            $_SESSION['user_role'] = $user['role']; // might be 'admin' in old system
            $_SESSION['user_is_admin'] = ($user['role'] === 'admin');
        }
        
        $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php?section=shop';
        unset($_SESSION['redirect_after_login']);
        redirect($redirect);
    } else {
        $error = 'Неверный ID или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/login-page.css">
</head>
<body class="login-page-body">
    <div class="login-page-background">
        <div class="login-bg-bottle login-bg-bottle-left"></div>
        <div class="login-bg-bottle login-bg-bottle-right"></div>
    </div>
    
    <div class="login-page-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="login-logo-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C8.5 2 5.5 4 4 7c-1.5 3-1.5 6 0 9 1.5 3 4.5 5 8 5s6.5-2 8-5c1.5-3 1.5-6 0-9C18.5 4 15.5 2 12 2z"/>
                        <path d="M12 8v8M8 12h8"/>
                        <path d="M9 9l6 6M15 9l-6 6"/>
                        <circle cx="12" cy="12" r="1" fill="currentColor"/>
                    </svg>
                </div>
                <div class="login-logo-text">ДенЛиФорс</div>
            </div>
            
            <h1 class="login-title">Авторизация</h1>
            
            <div class="login-badge">Авторизация в аккаунт</div>
            
            <?php if ($error): ?>
                <div class="login-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($info): ?>
                <div class="login-success"><?php echo $info; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="login-form-group">
                    <label class="login-label">ID</label>
                    <div class="login-input-wrapper">
                        <div class="login-input-icon login-icon-id">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="login" 
                            class="login-input" 
                            placeholder="Ваш регистрационный номер" 
                            required 
                            autofocus
                            value="<?php echo htmlspecialchars($prefillLogin); ?>"
                        >
                    </div>
                </div>
                
                <div class="login-form-group">
                    <label class="login-label">Пароль</label>
                    <div class="login-input-wrapper">
                        <div class="login-input-icon login-icon-password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            id="login-password" 
                            class="login-input" 
                            placeholder="Пароль" 
                            required
                        >
                        <button 
                            type="button" 
                            class="login-password-toggle" 
                            id="password-toggle"
                            aria-label="Показать пароль"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-eye">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-eye-off" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="login-forgot">
                    <a href="#" class="login-forgot-link">Забыли пароль?</a>
                </div>
                
                <button type="submit" class="login-submit-btn">
                    <span>Войти</span>
                </button>
            </form>
            
            <div class="login-register">
                <span class="login-register-text">Еще нет аккаунта?</span>
                <a href="register.php" class="login-register-link">Регистрация</a>
            </div>
        </div>
    </div>
    
    <script>
        // Переключение видимости пароля
        document.getElementById('password-toggle')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('login-password');
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeOff = this.querySelector('.icon-eye-off');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                iconEye.style.display = 'none';
                iconEyeOff.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                iconEye.style.display = 'block';
                iconEyeOff.style.display = 'none';
            }
        });
    </script>
</body>
</html>
