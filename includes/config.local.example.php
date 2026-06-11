<?php
declare(strict_types=1);

/**
 * Скопируйте в config.local.php и заполните своими значениями.
 * Файл config.local.php не должен попадать в git (.gitignore).
 */

// define('BASE_URL', 'http://localhost/Платформа%20тест');

define('FINKID_DEBUG', false);

define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your@email.com');
define('SMTP_PASS', 'app-password');
define('SMTP_FROM', 'your@email.com');
define('SMTP_FROM_NAME', 'FinKid');

// define('ADMIN_EMAIL', 'admin@example.com');
// define('ADMIN_SECRET_CODE', 'change-me');
