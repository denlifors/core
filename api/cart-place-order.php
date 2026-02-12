<?php
require_once '../config/config.php';
require_once '../includes/core-client.php';
require_once '../includes/partner-rank.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$db = getDBConnection();
$isAuth = isLoggedIn();

try {
    if ($isAuth) {
        $userStmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $userStmt->execute([':id' => (int)$_SESSION['user_id']]);
        $user = $userStmt->fetch();
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        $cartStmt = $db->prepare("
            SELECT c.id AS cart_id, c.product_id, c.quantity, p.name, p.price, p.sku, p.stock
            FROM cart c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = :uid
            ORDER BY c.created_at DESC
        ");
        $cartStmt->execute([':uid' => (int)$_SESSION['user_id']]);
    } else {
        $user = null;
        $sid = session_id();
        $cartStmt = $db->prepare("
            SELECT c.id AS cart_id, c.product_id, c.quantity, p.name, p.price, p.sku, p.stock
            FROM cart c
            JOIN products p ON p.id = c.product_id
            WHERE c.session_id = :sid
            ORDER BY c.created_at DESC
        ");
        $cartStmt->execute([':sid' => $sid]);
    }

    $cartItems = $cartStmt->fetchAll();
    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'error' => 'Корзина пуста']);
        exit;
    }

    $subtotal = 0.0;
    foreach ($cartItems as $item) {
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }

    $orderNumber = 'DL-' . date('Ymd') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    $db->beginTransaction();

    $orderStmt = $db->prepare("
        INSERT INTO orders (
            user_id, order_number, total, first_name, last_name, email, phone, address, city, postal_code, payment_method, notes, status
        ) VALUES (
            :user_id, :order_number, :total, :first_name, :last_name, :email, :phone, :address, :city, :postal_code, :payment_method, :notes, 'delivered'
        )
    ");
    $orderStmt->execute([
        ':user_id' => $isAuth ? (int)$_SESSION['user_id'] : null,
        ':order_number' => $orderNumber,
        ':total' => $subtotal,
        ':first_name' => $user['first_name'] ?? '',
        ':last_name' => $user['last_name'] ?? '',
        ':email' => $user['email'] ?? '',
        ':phone' => $user['phone'] ?? '',
        ':address' => '',
        ':city' => '',
        ':postal_code' => '',
        ':payment_method' => 'test',
        ':notes' => 'Быстрое оформление из корзины',
    ]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, product_sku, price, quantity)
        VALUES (:order_id, :product_id, :product_name, :product_sku, :price, :quantity)
    ");
    $stockStmt = $db->prepare("UPDATE products SET stock = GREATEST(COALESCE(stock,0) - :qty, 0) WHERE id = :id");

    foreach ($cartItems as $item) {
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => (int)$item['product_id'],
            ':product_name' => (string)$item['name'],
            ':product_sku' => (string)$item['sku'],
            ':price' => (float)$item['price'],
            ':quantity' => (int)$item['quantity'],
        ]);
        $stockStmt->execute([
            ':qty' => (int)$item['quantity'],
            ':id' => (int)$item['product_id'],
        ]);
    }

    if ($isAuth) {
        $db->prepare("DELETE FROM cart WHERE user_id = :uid")->execute([':uid' => (int)$_SESSION['user_id']]);
    } else {
        $db->prepare("DELETE FROM cart WHERE session_id = :sid")->execute([':sid' => session_id()]);
    }

    // Core sync for logged-in users.
    if ($isAuth) {
        $freshUserStmt = $db->prepare("SELECT id, email, role, consultant_id, core_user_id, core_partner_id, core_customer_id FROM users WHERE id = :id LIMIT 1");
        $freshUserStmt->execute([':id' => (int)$_SESSION['user_id']]);
        $freshUser = $freshUserStmt->fetch();

        if ($freshUser) {
            $wasPartnerBefore = !empty($freshUser['core_partner_id']) && (($freshUser['role'] ?? '') === 'partner');
            $buyerType = null;
            $buyerId = null;

            if (!empty($freshUser['core_partner_id']) && ($freshUser['role'] ?? '') === 'partner') {
                $buyerType = 'PARTNER';
                $buyerId = (string)$freshUser['core_partner_id'];
            } else {
                // Если в core уже есть партнёр с этим email (после прежних тестов),
                // привязываем его обратно локально и покупаем как партнёр.
                if (!empty($freshUser['email'])) {
                    $partnerLookupErr = null;
                    $partnerLookup = coreGetJson('/debug/partner-id?email=' . urlencode((string)$freshUser['email']), $partnerLookupErr);
                    if ($partnerLookup && ($partnerLookup['status'] ?? 500) < 400 && !empty($partnerLookup['data']['partnerId'])) {
                        $existingPartnerId = (string)$partnerLookup['data']['partnerId'];
                        $db->prepare("UPDATE users SET role = 'partner', core_partner_id = :pid WHERE id = :id")
                            ->execute([':pid' => $existingPartnerId, ':id' => (int)$_SESSION['user_id']]);
                        $_SESSION['user_role'] = 'partner';
                        $freshUser['role'] = 'partner';
                        $freshUser['core_partner_id'] = $existingPartnerId;
                        $buyerType = 'PARTNER';
                        $buyerId = $existingPartnerId;
                    }
                }

                if (empty($freshUser['core_customer_id']) && !empty($freshUser['email'])) {
                    $sponsorPartnerId = null;
                        if (!$buyerType) {
                        $sponsorStmt = $db->prepare("
                            SELECT sponsor_partner_id
                            FROM partner_registrations
                            WHERE email = :email
                            ORDER BY created_at DESC, id DESC
                            LIMIT 1
                        ");
                        $sponsorStmt->execute([':email' => (string)$freshUser['email']]);
                        $sponsorRow = $sponsorStmt->fetch();
                        if (!empty($sponsorRow['sponsor_partner_id'])) {
                            $candidateSponsor = (string)$sponsorRow['sponsor_partner_id'];
                            $sponsorCheckErr = null;
                            $sponsorCheck = coreGetJson('/partner-summary?partnerId=' . urlencode($candidateSponsor), $sponsorCheckErr);
                            if ($sponsorCheck && ($sponsorCheck['status'] ?? 500) < 400) {
                                $sponsorPartnerId = $candidateSponsor;
                            }
                        }
                    }

                    // Fallback: привязка через consultant_id (клиентская ссылка register.php?consultant_id=...).
                    // consultant_id может быть:
                    // 1) id пользователя в users (приоритетный текущий формат),
                    // 2) id записи в partner_registrations (legacy).
                    $consultantRef = (int)($freshUser['consultant_id'] ?? ($user['consultant_id'] ?? 0));
                    if (!$buyerType && !$sponsorPartnerId && $consultantRef > 0) {
                        $consultantId = $consultantRef;
                        if ($consultantId > 0) {
                            $userMapStmt = $db->prepare("
                                SELECT core_partner_id
                                FROM users
                                WHERE id = :uid
                                  AND role = 'partner'
                                  AND core_partner_id IS NOT NULL
                                LIMIT 1
                            ");
                            $userMapStmt->execute([':uid' => $consultantId]);
                            $userMap = $userMapStmt->fetch();
                            if (!empty($userMap['core_partner_id'])) {
                                $candidateSponsor = (string)$userMap['core_partner_id'];
                                $sponsorCheckErr = null;
                                $sponsorCheck = coreGetJson('/partner-summary?partnerId=' . urlencode($candidateSponsor), $sponsorCheckErr);
                                if ($sponsorCheck && ($sponsorCheck['status'] ?? 500) < 400) {
                                    $sponsorPartnerId = $candidateSponsor;
                                }
                            }

                            if (!$sponsorPartnerId) {
                                $regMapStmt = $db->prepare("
                                    SELECT core_partner_id
                                    FROM partner_registrations
                                    WHERE id = :rid
                                      AND status = 'confirmed'
                                    LIMIT 1
                                ");
                                $regMapStmt->execute([':rid' => $consultantId]);
                                $regMap = $regMapStmt->fetch();
                                if (!empty($regMap['core_partner_id'])) {
                                    $candidateSponsor = (string)$regMap['core_partner_id'];
                                    $sponsorCheckErr = null;
                                    $sponsorCheck = coreGetJson('/partner-summary?partnerId=' . urlencode($candidateSponsor), $sponsorCheckErr);
                                    if ($sponsorCheck && ($sponsorCheck['status'] ?? 500) < 400) {
                                        $sponsorPartnerId = $candidateSponsor;
                                    }
                                }
                            }
                        }
                    }

                    if (!$buyerType) {
                        $registerCustomerErr = null;
                        $registerCustomer = corePostJson('/register-customer', [
                            'email' => (string)$freshUser['email'],
                            'password' => 'tmp_' . bin2hex(random_bytes(8)),
                            'sponsorPartnerId' => $sponsorPartnerId,
                        ], $registerCustomerErr);

                        if ($registerCustomer && ($registerCustomer['status'] ?? 500) < 400) {
                            $coreUserId = $registerCustomer['data']['user']['id'] ?? null;
                            $coreCustomerId = $registerCustomer['data']['customer']['id'] ?? null;
                            if ($coreCustomerId) {
                                $db->prepare("
                                    UPDATE users
                                    SET core_user_id = COALESCE(:core_user_id, core_user_id),
                                        core_customer_id = :core_customer_id
                                    WHERE id = :id
                                ")->execute([
                                    ':core_user_id' => $coreUserId,
                                    ':core_customer_id' => $coreCustomerId,
                                    ':id' => (int)$_SESSION['user_id'],
                                ]);
                                $freshUser['core_customer_id'] = $coreCustomerId;
                            }
                        }
                    }
                }

                if (!empty($freshUser['core_customer_id'])) {
                    $buyerType = 'CUSTOMER';
                    $buyerId = (string)$freshUser['core_customer_id'];
                }
            }

            $payloadDvTotal = 0;
            if ($buyerType && $buyerId) {
                $itemsPayload = [];
                foreach ($cartItems as $item) {
                    $linePrice = (float)$item['price'] * (int)$item['quantity'];
                    $lineDv = max(1, (int)floor($linePrice / 30));
                    $payloadDvTotal += $lineDv;
                    $itemsPayload[] = [
                        'name' => (string)$item['name'],
                        'priceRub' => $linePrice,
                        'dv' => $lineDv,
                    ];
                }

                $corePurchaseErr = null;
                $coreResult = corePostJson('/purchase', [
                    'buyerType' => $buyerType,
                    'buyerId' => $buyerId,
                    'items' => $itemsPayload,
                    'useCashbackRub' => 0,
                ], $corePurchaseErr);

                if (!$coreResult || ($coreResult['status'] ?? 500) >= 400) {
                    $coreMsg = $corePurchaseErr ?: 'Core purchase request failed';
                    if (!empty($coreResult['data']['error'])) {
                        $coreMsg = (string)$coreResult['data']['error'];
                    }
                    throw new RuntimeException('Core purchase failed: ' . $coreMsg);
                }

                if ($coreResult && isset($coreResult['data']['upgradedPartner']['id'])) {
                    $newPartnerId = (string)$coreResult['data']['upgradedPartner']['id'];
                    $db->prepare("UPDATE users SET role = 'partner', core_partner_id = :pid WHERE id = :id")
                        ->execute([':pid' => $newPartnerId, ':id' => (int)$_SESSION['user_id']]);
                    // Синхронизируем текущий partnerId в таблице регистраций,
                    // чтобы реф-ссылки через registration id не уезжали в старый sponsor.
                    if (!empty($freshUser['email'])) {
                        $db->prepare("
                            UPDATE partner_registrations
                            SET core_partner_id = :pid
                            WHERE email = :email
                              AND status = 'confirmed'
                        ")->execute([
                            ':pid' => $newPartnerId,
                            ':email' => (string)$freshUser['email'],
                        ]);
                    }
                    $_SESSION['user_role'] = 'partner';
                }
            }

            if (!$buyerType || !$buyerId) {
                throw new RuntimeException('Core buyer identity not resolved');
            }

            $mustBecomePartnerNow = (!$wasPartnerBefore) && ($payloadDvTotal >= 200);

            if ($mustBecomePartnerNow) {
                $checkStmt = $db->prepare("SELECT role, core_partner_id FROM users WHERE id = :id LIMIT 1");
                $checkStmt->execute([':id' => (int)$_SESSION['user_id']]);
                $afterUser = $checkStmt->fetch();
                $isPartnerAfter = $afterUser && ($afterUser['role'] ?? '') === 'partner' && !empty($afterUser['core_partner_id']);
                if (!$isPartnerAfter) {
                    throw new RuntimeException('Core did not upgrade user to partner after purchase');
                }
            }

        }
    }

    $db->commit();

    // Синхронизацию ранга и событий делаем после commit:
    // внутри syncUserRankEvent может выполняться DDL-проверка таблицы, которая ломает активную транзакцию.
    if ($isAuth) {
        try {
            $syncUserStmt = $db->prepare("SELECT id, role, core_partner_id FROM users WHERE id = :id LIMIT 1");
            $syncUserStmt->execute([':id' => (int)$_SESSION['user_id']]);
            $syncUser = $syncUserStmt->fetch();
            if ($syncUser) {
                syncUserRankEvent($db, $syncUser);
            }
        } catch (Throwable $syncErr) {
            error_log('cart-place-order rank sync failed: ' . $syncErr->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'order_number' => $orderNumber,
        'redirect' => BASE_URL . 'dashboard.php?section=team',
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        try {
            $db->rollBack();
        } catch (Throwable $rollbackErr) {
            // Ignore rollback edge cases when transaction is already implicitly closed.
        }
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

