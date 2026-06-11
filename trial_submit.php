<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf', 'msg' => __('auth.err_csrf')]);
    exit;
}

$ans1 = strtoupper(trim((string) ($_POST['answer1'] ?? '')));

$ans2 = $_POST['answer2'] ?? [];
if (!is_array($ans2)) {
    $ans2 = $ans2 !== '' ? [(string) $ans2] : [];
}

$ok1 = ($ans1 === 'A');

$q2 = [
    'type' => 'multiple',
    'correct_answer' => 'A,B',
];
$ok2 = checkQuestionAnswer($q2, $ans2);

$passed = $ok1 && $ok2;

if ($passed) {
    $_SESSION['trial_completed'] = true;
    $_SESSION['trial_lesson_id'] = TRIAL_LESSON_ID;
}

echo json_encode([
    'ok' => true,
    'passed' => $passed,
    'msg' => $passed ? __('trial.pass') : __('trial.fail'),
    'msg_key' => $passed ? 'trial.pass' : 'trial.fail',
]);