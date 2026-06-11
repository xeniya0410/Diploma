<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuth()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
if (!is_array($data)) {
    $data = $_POST;
}

$token = (string)($data['csrf_token'] ?? $_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$amount = (int)($data['amount'] ?? 0);
$courseId = (int)($data['course_id'] ?? 0);
$step = (int)($data['step'] ?? 0);

if ($amount < 1 || $amount > 50) {
    echo json_encode(['ok' => false, 'error' => 'amount']);
    exit;
}

$key = 'v4_calc_' . $courseId . '_' . $step;
if (!empty($_SESSION[$key])) {
    $xp = getUserXp($pdo, $userId);
    echo json_encode(['ok' => true, 'xp' => $xp, 'level' => getUserLevel($xp), 'duplicate' => true]);
    exit;
}

$xp = addUserXp($pdo, $userId, $amount);
$_SESSION[$key] = true;

echo json_encode([
    'ok'    => true,
    'xp'    => $xp,
    'level' => getUserLevel($xp),
]);
