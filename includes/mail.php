<?php
declare(strict_types=1);

function mailLog(string $message): void
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    @file_put_contents($dir . '/mail.log', $line, FILE_APPEND | LOCK_EX);
}

function sendMail(string $to, string $subject, string $bodyHtml, ?string $attachmentPath = null): bool
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (SMTP_HOST !== '' && is_file($autoload)) {
        require_once $autoload;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_USER !== '';
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $port             = (int)SMTP_PORT;
            $mail->Port       = $port;
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            if ($attachmentPath && is_file($attachmentPath)) {
                $ext  = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                $name = $ext === 'pdf' ? 'certificate-finkid.pdf' : 'certificate.html';
                $mail->addAttachment($attachmentPath, $name);
            }
            $mail->send();
            return true;
        } catch (Throwable $e) {
            mailLog('SMTP error to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    if (SMTP_HOST === '') {
        mailLog('SMTP_HOST пустой — письмо не отправлено (to ' . $to . ')');
    } elseif (!is_file($autoload)) {
        mailLog('Нет vendor/autoload.php — выполните composer install');
    }

    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $ok = @mail($to, $subject, $bodyHtml, $headers);
    if (!$ok) {
        mailLog('mail() failed for ' . $to);
    }
    return $ok;
}

function sendCertificateEmail(string $to, string $userName, string $courseTitle, string $filePath, string $code): bool
{
    $body = '<div style="font-family:Nunito,sans-serif;max-width:520px">
    <h2 style="color:#2D7DD2">' . e(__('mail.cert.heading')) . '</h2>
    <p>' . e(__f('mail.cert.congrats', $userName)) . '</p>
    <p>' . e(__f('mail.cert.completed', $courseTitle)) . '</p>
    <p>' . e(__f('mail.cert.code', $code)) . '</p>
    <p style="color:#6b82a8;font-size:14px">' . e(__('mail.cert.attachment')) . '</p>
    </div>';
    return sendMail($to, __f('mail.cert.subject', $courseTitle), $body, $filePath);
}

/** Абсолютная ссылка на страницу (для писем). */
function mailAbsoluteUrl(string $path): string
{
    $relative = asset($path);
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $relative;
    }
    $base = rtrim(defined('BASE_URL') ? (string)BASE_URL : 'http://localhost', '/');

    return $base . $relative;
}

function mailButton(string $href, string $label): string
{
    return '<p style="margin:24px 0"><a href="' . e($href) . '" style="display:inline-block;padding:12px 24px;background:#2d7dd2;color:#fff;text-decoration:none;border-radius:10px;font-weight:700">' . e($label) . '</a></p>';
}

/** Уведомление администратору о новой заявке преподавателя. */
function sendNewTeacherApplicationAdminEmail(
    string $adminEmail,
    string $teacherName,
    string $teacherEmail,
    string $organization,
    string $experience,
    string $adminPanelUrl
): bool {
    $org = $organization !== '' ? e($organization) : '—';
    $exp = $experience !== '' ? nl2br(e($experience)) : '—';
    $body = '<div style="font-family:Nunito,Arial,sans-serif;max-width:560px;color:#1e2a3a">
    <h2 style="color:#2d7dd2;margin:0 0 12px">' . e(__('mail.admin_teacher.heading')) . '</h2>
    <p>' . e(__('mail.admin_teacher.intro')) . '</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:15px">
      <tr><td style="padding:8px 0;color:#6b82a8">' . e(__('mail.admin_teacher.name')) . '</td><td style="padding:8px 0"><strong>' . e($teacherName) . '</strong></td></tr>
      <tr><td style="padding:8px 0;color:#6b82a8">' . e(__('mail.admin_teacher.email')) . '</td><td style="padding:8px 0"><a href="mailto:' . e($teacherEmail) . '">' . e($teacherEmail) . '</a></td></tr>
      <tr><td style="padding:8px 0;color:#6b82a8">' . e(__('mail.admin_teacher.org')) . '</td><td style="padding:8px 0">' . $org . '</td></tr>
      <tr><td style="padding:8px 0;color:#6b82a8;vertical-align:top">' . e(__('mail.admin_teacher.exp')) . '</td><td style="padding:8px 0">' . $exp . '</td></tr>
    </table>
    ' . mailButton($adminPanelUrl, __('mail.admin_teacher.btn')) . '
    <p style="font-size:13px;color:#6b82a8">' . e(__('mail.admin_teacher.hint')) . '</p>
    </div>';

    $ok = sendMail($adminEmail, __('mail.admin_teacher.subject'), $body);
    mailLog('teacher application notify admin ' . $adminEmail . ': ' . ($ok ? 'OK' : 'FAIL'));
    return $ok;
}

/** Письмо преподавателю после одобрения заявки. */
function sendTeacherApprovedEmail(string $to, string $teacherName, string $loginUrl): bool
{
    $body = '<div style="font-family:Nunito,Arial,sans-serif;max-width:560px;color:#1e2a3a">
    <h2 style="color:#0ab5a6;margin:0 0 12px">' . e(__('mail.teacher_approved.heading')) . '</h2>
    <p>' . e(__f('mail.teacher_approved.greeting', $teacherName)) . '</p>
    <p>' . e(__('mail.teacher_approved.body')) . '</p>
    ' . mailButton($loginUrl, __('mail.teacher_approved.btn')) . '
    <p style="font-size:13px;color:#6b82a8">' . e(__('mail.teacher_approved.hint')) . '</p>
    </div>';

    $ok = sendMail($to, __('mail.teacher_approved.subject'), $body);
    mailLog('teacher approved notify ' . $to . ': ' . ($ok ? 'OK' : 'FAIL'));
    return $ok;
}
