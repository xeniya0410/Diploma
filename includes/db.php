<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'finkid');
define('DB_USER', 'root');
// UniServer: часто пароль пустой — укажите '' или 'root'
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    $msg = $e->getMessage();
    $isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    if ($isCli) {
        fwrite(STDERR, "MySQL error: {$msg}\n");
        if (stripos($msg, 'could not find driver') !== false) {
            fwrite(STDERR, "Включите extension=pdo_mysql в php.ini (CLI) или запустите:\n");
            fwrite(STDERR, "  UniServerZ\\core\\php83\\php.exe database/install_admin.php\n");
        } else {
            fwrite(STDERR, "Создайте БД finkid, импортируйте database/schema.sql, проверьте includes/db.php\n");
        }
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>ФинКид — БД</title></head><body style="font-family:sans-serif;padding:2rem;max-width:600px">';
    echo '<h1>Нет подключения к MySQL</h1>';
    if (stripos($msg, 'could not find driver') !== false) {
        echo '<p>В PHP не включён драйвер <strong>pdo_mysql</strong>. В UniServer откройте <code>core/php83/php_production.ini</code> и убедитесь, что есть <code>extension=pdo_mysql</code>, затем перезапустите Apache.</p>';
    } else {
        echo '<p>Создайте базу <strong>finkid</strong> и импортируйте <code>database/schema.sql</code> в phpMyAdmin.</p>';
        echo '<p>Проверьте логин/пароль в <code>includes/db.php</code> (UniServer: часто root / пустой пароль).</p>';
    }
    echo '<p style="color:#666;font-size:14px">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit;
}
