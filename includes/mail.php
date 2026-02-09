<?php
function sendHtmlMail($to, $subject, $htmlBody, $textBody = null) {
    $from = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'No Reply';

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $headers[] = "From: {$encodedFromName} <{$from}>";

    $result = @mail($to, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    if ($result) {
        return true;
    }

    // Fallback: write to log for local testing
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $logPath = $logDir . '/mail.log';
    $text = $textBody ?: strip_tags($htmlBody);
    $entry = "[" . date('Y-m-d H:i:s') . "] TO={$to} SUBJECT={$subject}\n{$text}\n\n";
    @file_put_contents($logPath, $entry, FILE_APPEND);
    return false;
}

function sendPartnerConfirmEmail($email, $name, $confirmLink, $registrationNumber, $password) {
    $subject = 'Подтверждение регистрации партнёра';
    $safeName = htmlspecialchars($name);
    $safeLink = htmlspecialchars($confirmLink);
    $safeReg = htmlspecialchars((string)$registrationNumber);
    $safePass = htmlspecialchars($password);

    $html = "
        <div style=\"font-family: Arial, sans-serif; line-height: 1.5;\">
            <h2>Подтверждение регистрации</h2>
            <p>Здравствуйте, {$safeName}!</p>
            <p><strong>Ваш регистрационный номер:</strong> {$safeReg}</p>
    <p><strong>Пароль:</strong> {$safePass}</p>
            <p>Подтвердите вашу регистрацию партнёра, перейдя по ссылке:</p>
            <p><a href=\"{$safeLink}\">{$safeLink}</a></p>
            <p>Если вы не оставляли заявку, просто игнорируйте это письмо.</p>
        </div>
    ";

    $text = "Здравствуйте, {$name}!\nВаш регистрационный номер: {$registrationNumber}\nПароль: {$password}\nПодтвердите регистрацию партнёра: {$confirmLink}\n";
    return sendHtmlMail($email, $subject, $html, $text);
}
?>
