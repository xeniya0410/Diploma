<?php
declare(strict_types=1);

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}


if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost');
}

if (!defined('PASS_PERCENT')) {
    define('PASS_PERCENT', 70);
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', '');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587);
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', '');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', '');
}
if (!defined('SMTP_FROM')) {
    define('SMTP_FROM', 'noreply@finkid.local');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'ФинКид');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'finkidkz@gmail.com');
}

/** WhatsApp поддержки (укажите номер: 77001234567 без +) */
if (!defined('WHATSAPP_URL')) {
    define('WHATSAPP_URL', 'https://wa.me/77079121035');
}

/** ID урока для пробного прохождения на главной (первый урок бесплатного курса) */
if (!defined('TRIAL_LESSON_ID')) {
    define('TRIAL_LESSON_ID', 1);
}

/** Секретный код для регистрации администратора */
if (!defined('ADMIN_SECRET_CODE')) {
    define('ADMIN_SECRET_CODE', 'finkid2026');
}

/** false в config.local.php на продакшене — скрыть ошибки PHP в браузере */
if (!defined('FINKID_DEBUG')) {
    define('FINKID_DEBUG', true);
}
