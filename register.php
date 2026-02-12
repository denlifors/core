<?php
require_once 'config/config.php';
require_once 'includes/mail.php';
require_once 'includes/core-client.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$ref = sanitize($_GET['ref'] ?? $_POST['consultant_id'] ?? '');
$consultantPreset = sanitize($_GET['consultant_id'] ?? '');
$isPartnerFlow = !empty($ref) && (($_GET['flow'] ?? '') === 'partner');
$hasConsultantPreset = !empty($ref) || !empty($consultantPreset);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $birthDate = sanitize($_POST['birth_date'] ?? '');
    $consultantId = sanitize($_POST['consultant_id'] ?? '');
    $privacyAgree = isset($_POST['privacy_agree']);
    $termsAgree = isset($_POST['terms_agree']);
    
    // Валидация reCAPTCHA (если используется)
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    if (!$privacyAgree || !$termsAgree) {
        $error = 'Необходимо согласиться с условиями использования';
    } elseif ($password !== $confirmPassword) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $db = getDBConnection();
        
        // Проверяем наличие колонки birth_date
        try {
            $db->query("SELECT birth_date FROM users LIMIT 1");
        } catch (PDOException $e) {
            // Добавляем колонку если её нет
            try {
                $db->exec("ALTER TABLE users ADD COLUMN birth_date DATE NULL");
            } catch (PDOException $e2) {
                // Игнорируем если колонка уже существует
            }
        }
        
        // Проверяем наличие колонки consultant_id
        try {
            $db->query("SELECT consultant_id FROM users LIMIT 1");
        } catch (PDOException $e) {
            // Добавляем колонку если её нет
            try {
                $db->exec("ALTER TABLE users ADD COLUMN consultant_id INT NULL");
            } catch (PDOException $e2) {
                // Игнорируем если колонка уже существует
            }
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            if ($isPartnerFlow) {
                if (!$ref) {
                    $error = 'Регистрация партнёра возможна только по реферальной ссылке';
                } else {
                    try {
                        $db->query("SELECT id FROM partner_registrations LIMIT 1");
                    } catch (PDOException $e) {
                        $error = 'Таблица partner_registrations не найдена. Запустите update-db-core.php';
                    }
                }

                if (!$error) {
                    $stmt = $db->prepare("SELECT id FROM partner_registrations WHERE email = :email AND status = 'pending' LIMIT 1");
                    $stmt->execute([':email' => $email]);
                    if ($stmt->fetch()) {
                        $error = 'Заявка уже отправлена. Проверьте почту для подтверждения.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $token = bin2hex(random_bytes(16));
                        $fullName = trim($firstName . ' ' . $lastName);

                        $stmt = $db->prepare(
                            "INSERT INTO partner_registrations
                             (name, first_name, last_name, email, phone, password_plain, password_hash, token, status, sponsor_partner_id)
                             VALUES (:name, :first_name, :last_name, :email, :phone, :password_plain, :password_hash, :token, 'pending', :sponsor_partner_id)"
                        );
                        $stmt->execute([
                            ':name' => $fullName ?: $firstName,
                            ':first_name' => $firstName,
                            ':last_name' => $lastName,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':password_plain' => $password,
                            ':password_hash' => $hashedPassword,
                            ':token' => $token,
                            ':sponsor_partner_id' => $ref
                        ]);

                        $registrationNumber = (int)$db->lastInsertId();

                        if (defined('PARTNER_AUTO_CONFIRM') && PARTNER_AUTO_CONFIRM) {
                            $coreErr = null;
                            $coreResult = corePostJson('/register-partner', [
                                'email' => $email,
                                'password' => $password,
                                'sponsorPartnerId' => $ref,
                            ], $coreErr);

                            if (!$coreResult || ($coreResult['status'] ?? 500) >= 400) {
                                $error = 'Ошибка регистрации в ядре. Повторите ещё раз.';
                            } else {
                                $coreUserId = $coreResult['data']['user']['id'] ?? null;
                                $corePartnerId = $coreResult['data']['partner']['id'] ?? null;

                                if (!$coreUserId || !$corePartnerId) {
                                    $error = 'Ядро вернуло неполные данные партнёра.';
                                } else {
                                    $db->prepare(
                                        "UPDATE partner_registrations
                                         SET status = 'confirmed',
                                             confirmed_at = NOW(),
                                             core_user_id = :core_user_id,
                                             core_partner_id = :core_partner_id,
                                             core_customer_id = NULL,
                                             password_plain = NULL
                                         WHERE id = :id"
                                    )->execute([
                                        ':core_user_id' => $coreUserId,
                                        ':core_partner_id' => $corePartnerId,
                                        ':id' => $registrationNumber,
                                    ]);

                                    $existingUserStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                                    $existingUserStmt->execute([':email' => $email]);
                                    $existingUser = $existingUserStmt->fetch();

                                    if ($existingUser) {
                                        $db->prepare(
                                            "UPDATE users
                                             SET role = 'partner',
                                                 core_user_id = :core_user_id,
                                                 core_partner_id = :core_partner_id,
                                                 core_customer_id = NULL
                                             WHERE id = :id"
                                        )->execute([
                                            ':core_user_id' => $coreUserId,
                                            ':core_partner_id' => $corePartnerId,
                                            ':id' => (int)$existingUser['id'],
                                        ]);
                                    } else {
                                        $db->prepare(
                                            "INSERT INTO users (email, password, first_name, last_name, phone, role, consultant_id, core_user_id, core_partner_id, core_customer_id)
                                             VALUES (:email, :password, :first_name, :last_name, :phone, 'partner', :consultant_id, :core_user_id, :core_partner_id, NULL)"
                                        )->execute([
                                            ':email' => $email,
                                            ':password' => $hashedPassword,
                                            ':first_name' => $firstName,
                                            ':last_name' => $lastName,
                                            ':phone' => $phone,
                                            ':consultant_id' => is_numeric($consultantId) ? (int)$consultantId : null,
                                            ':core_user_id' => $coreUserId,
                                            ':core_partner_id' => $corePartnerId,
                                        ]);
                                    }

                                    redirect('login.php?confirmed=1&reg_id=' . $registrationNumber);
                                }
                            }
                        } else {
                            $confirmLink = BASE_URL . 'partner-confirm.php?token=' . $token;
                            $mailSent = sendPartnerConfirmEmail($email, $fullName ?: $firstName, $confirmLink, $registrationNumber, $password);

                            if ($mailSent) {
                                $success = 'Письмо подтверждения отправлено на ваш email. Перейдите по ссылке для завершения регистрации.';
                            } else {
                                $success = 'Заявка создана. Письмо не отправлено (локальная среда). Проверьте файл logs/mail.log.';
                            }
                        }
                    }
                }
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $resolvedConsultantId = null;
                if ($consultantId !== '') {
                    if (is_numeric($consultantId)) {
                        $candidateId = (int)$consultantId;

                        // Предпочтительно храним consultant_id как users.id активного партнёра.
                        $userByIdStmt = $db->prepare("
                            SELECT id
                            FROM users
                            WHERE id = :id
                              AND role = 'partner'
                              AND core_partner_id IS NOT NULL
                            LIMIT 1
                        ");
                        $userByIdStmt->execute([':id' => $candidateId]);
                        $userById = $userByIdStmt->fetch();
                        if ($userById && !empty($userById['id'])) {
                            $resolvedConsultantId = (int)$userById['id'];
                        }

                        // Если это registration id, пробуем найти email и затем users.id партнёра.
                        if ($resolvedConsultantId === null) {
                            $regByIdStmt = $db->prepare("
                                SELECT email
                                FROM partner_registrations
                                WHERE id = :id
                                  AND status = 'confirmed'
                                LIMIT 1
                            ");
                            $regByIdStmt->execute([':id' => $candidateId]);
                            $regById = $regByIdStmt->fetch();
                            if ($regById && !empty($regById['email'])) {
                                $userByEmailStmt = $db->prepare("
                                    SELECT id
                                    FROM users
                                    WHERE email = :email
                                      AND role = 'partner'
                                      AND core_partner_id IS NOT NULL
                                    LIMIT 1
                                ");
                                $userByEmailStmt->execute([':email' => (string)$regById['email']]);
                                $userByEmail = $userByEmailStmt->fetch();
                                if ($userByEmail && !empty($userByEmail['id'])) {
                                    $resolvedConsultantId = (int)$userByEmail['id'];
                                }
                            }
                        }

                        // Крайний fallback: сохраняем как есть.
                        if ($resolvedConsultantId === null) {
                            $resolvedConsultantId = $candidateId;
                        }
                    } else {
                        // Совместимость со старыми ссылками вида register.php?ref=<core_partner_id>
                        $mapStmt = $db->prepare("
                            SELECT id
                            FROM partner_registrations
                            WHERE core_partner_id = :pid
                              AND status = 'confirmed'
                            ORDER BY confirmed_at DESC, id DESC
                            LIMIT 1
                        ");
                        $mapStmt->execute([':pid' => $consultantId]);
                        $mapped = $mapStmt->fetch();
                        if ($mapped && !empty($mapped['id'])) {
                            $resolvedConsultantId = (int)$mapped['id'];
                        } else {
                            $mapUserStmt = $db->prepare("
                                SELECT id
                                FROM users
                                WHERE core_partner_id = :pid
                                  AND role = 'partner'
                                LIMIT 1
                            ");
                            $mapUserStmt->execute([':pid' => $consultantId]);
                            $mappedUser = $mapUserStmt->fetch();
                            if ($mappedUser && !empty($mappedUser['id'])) {
                                $resolvedConsultantId = (int)$mappedUser['id'];
                            }
                        }
                    }
                }

                // Обычная регистрация клиента
                $insertData = [
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':phone' => $phone,
                    ':role' => 'user'
                ];

                $sql = "INSERT INTO users (email, password, first_name, last_name, phone, role";
                $values = "VALUES (:email, :password, :first_name, :last_name, :phone, :role";

                if ($birthDate) {
                    $sql .= ", birth_date";
                    $values .= ", :birth_date";
                    $insertData[':birth_date'] = $birthDate;
                }

                if ($resolvedConsultantId !== null) {
                    $sql .= ", consultant_id";
                    $values .= ", :consultant_id";
                    $insertData[':consultant_id'] = $resolvedConsultantId;
                }

                $sql .= ") " . $values . ")";

                $stmt = $db->prepare($sql);
                $stmt->execute($insertData);

                // Auto login
                $userId = $db->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';

                redirect('index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/login-page.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/register-page.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="register-page-body">
    <div class="login-page-background">
        <div class="login-bg-bottle login-bg-bottle-left"></div>
        <div class="login-bg-bottle login-bg-bottle-right"></div>
    </div>
    
    <div class="login-page-container">
        <div class="register-card">
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
            
            <h1 class="login-title">Регистрация</h1>

            <?php if ($isPartnerFlow): ?>
                <div class="register-badge">Партнёр</div>
                <div class="register-promo-banner">
                    <p>Регистрация партнёра по реферальной ссылке.<br>После подтверждения вы попадёте в кабинет и сможете сделать первую покупку.</p>
                </div>
            <?php else: ?>
                <div class="register-badge">Клиент</div>
                <div class="register-promo-banner">
                    <p>Стань клиентом <strong>ДенЛиФорс</strong><br>и получай кэшбек 15% с каждой покупки!</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="login-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="login-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="register-form" id="registerForm">
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-user">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="last_name" 
                            class="register-input" 
                            placeholder="Фамилия" 
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-user">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="first_name" 
                            class="register-input" 
                            placeholder="Имя" 
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-calendar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <input 
                            type="date" 
                            name="birth_date" 
                            class="register-input" 
                            placeholder="Дата рождения"
                            value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>"
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-phone">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <input 
                            type="tel" 
                            name="phone" 
                            class="register-input" 
                            placeholder="Номер телефона"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-email">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <input 
                            type="email" 
                            name="email" 
                            class="register-input" 
                            placeholder="E-mail"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="register-consultant-section">
                    <p class="register-consultant-text">
                        Укажите Регистрационный номер Консультанта Компании,<br>
                        который рекомендовал вам продукцию ДенЛиФорс.
                    </p>
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-id">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <input
                            type="text" 
                            name="consultant_id" 
                            class="register-input" 
                            placeholder="Введите регистрационный номер"
                            value="<?php echo htmlspecialchars($ref ?: ($_POST['consultant_id'] ?? $consultantPreset)); ?>"
                            <?php if ($hasConsultantPreset): ?>readonly required<?php endif; ?>
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            id="register-password" 
                            class="register-input" 
                            placeholder="Пароль" 
                            required
                            minlength="6"
                        >
                    </div>
                </div>
                
                <div class="register-form-group">
                    <div class="register-input-wrapper">
                        <div class="register-input-icon register-icon-password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="register-input" 
                            placeholder="Подтвердите пароль" 
                            required
                            minlength="6"
                        >
                    </div>
                </div>
                
                <div class="register-checkboxes">
                    <label class="register-checkbox-label">
                        <input type="checkbox" name="privacy_agree" class="register-checkbox" required>
                        <span class="register-checkbox-custom"></span>
                        <span class="register-checkbox-text">
                            Подтверждаю согласие <a href="privacy.php" target="_blank" class="register-link">на обработку персональных данных</a> в соответствии с <a href="privacy.php" target="_blank" class="register-link">Политикой в области обработки персональных данных</a>.
                        </span>
                    </label>
                    
                    <label class="register-checkbox-label">
                        <input type="checkbox" name="terms_agree" class="register-checkbox" required>
                        <span class="register-checkbox-custom"></span>
                        <span class="register-checkbox-text">
                            Принимаю условия <a href="terms.php" target="_blank" class="register-link">Пользовательского Соглашения</a>.
                        </span>
                    </label>
                </div>
                
                <div class="register-recaptcha">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                </div>
                
                <button type="submit" class="login-submit-btn" id="registerSubmitBtn">
                    <span>Регистрация</span>
                </button>
            </form>
            
            <div class="login-register">
                <span class="login-register-text">Уже есть аккаунт?</span>
                <a href="login.php" class="login-register-link">Войти</a>
            </div>
        </div>
    </div>
    
    <script>
        // Маска для телефона
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 1) {
                        value = '+' + value;
                    } else if (value.length <= 4) {
                        value = '+' + value.substring(0, 1) + ' (' + value.substring(1);
                    } else if (value.length <= 7) {
                        value = '+' + value.substring(0, 1) + ' (' + value.substring(1, 4) + ') ' + value.substring(4);
                    } else if (value.length <= 9) {
                        value = '+' + value.substring(0, 1) + ' (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7);
                    } else {
                        value = '+' + value.substring(0, 1) + ' (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7, 9) + '-' + value.substring(9, 11);
                    }
                }
                e.target.value = value;
            });
        }
        
        // Проверка чекбоксов для активации кнопки
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('registerSubmitBtn');
        const checkboxes = form.querySelectorAll('.register-checkbox');
        
        function checkFormValidity() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            submitBtn.disabled = !allChecked;
            if (!allChecked) {
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', checkFormValidity);
        });
        
        // Инициализация при загрузке
        checkFormValidity();
    </script>
</body>
</html>
