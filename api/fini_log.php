<?php
declare(strict_types=1);

/**
 * Лог диалогов виджета Фини (таблица chat_logs).
 * Имя файла без «chat» — на части хостингов путь api/chat_log.php блокируется.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$q = trim($_POST['question'] ?? '');
$a = trim($_POST['answer'] ?? '');
$esc = !empty($_POST['escalated']) ? 1 : 0;
$uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

if ($q === '' || mb_strlen($q) > 2000 || mb_strlen($a) > 4000) {
    echo json_encode(['ok' => false, 'error' => 'validation']);
    exit;
}

$_SESSION['fini_log_count'] = (int) ($_SESSION['fini_log_count'] ?? 0);
if ($_SESSION['fini_log_count'] > 120) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate']);
    exit;
}
$_SESSION['fini_log_count']++;

try {
    $pdo->prepare(
        'INSERT INTO chat_logs (user_id, question, answer, escalated, created_at) VALUES (?,?,?,?,NOW())'
    )->execute([$uid ?: null, $q, $a, $esc]);
    commitSession();
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    if (defined('FINKID_DEBUG') && FINKID_DEBUG) {
        error_log('fini_log: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db']);
}
