<?php
/**
 * Создание администратора (после импорта database/schema.sql в пустую базу).
 * Запуск из CLI: php database/install_admin.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$email = ADMIN_EMAIL;
$pass  = 'Admin123!';
$hash  = password_hash($pass, PASSWORD_BCRYPT);
$name  = 'Администратор';

$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
    $pdo->prepare('UPDATE users SET password_hash = ?, role = "admin", name = ? WHERE email = ?')
        ->execute([$hash, $name, $email]);
    echo "Админ обновлён: {$email} / {$pass}\n";
} else {
    $pdo->prepare('INSERT INTO users (name, age, email, password_hash, role) VALUES (?,?,?,?,?)')
        ->execute([$name, 30, $email, $hash, 'admin']);
    echo "Админ создан: {$email} / {$pass}\n";
}
