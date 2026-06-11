<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/v4_courses.php';

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

$token = (string)($data['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$courseId = (int)($data['course_id'] ?? 0);
$lessonId = (int)($data['lesson_id'] ?? 0);

$courseSt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$courseSt->execute([$courseId]);
$course = $courseSt->fetch();
if (!$course) {
    echo json_encode(['ok' => false, 'error' => 'course']);
    exit;
}

$slug = courseSlug($course);
$v4   = getV4CourseBySlug($slug);
if (!$v4) {
    echo json_encode(['ok' => false, 'error' => 'v4']);
    exit;
}

if ($lessonId < 1) {
    $ls = $pdo->prepare('SELECT id FROM lessons WHERE course_id = ? ORDER BY sort_order, id LIMIT 1');
    $ls->execute([$courseId]);
    $lessonId = (int)($ls->fetchColumn() ?: 0);
}

$xpReward = (int)($v4['xp'] ?? $course['xp_reward'] ?? 30);
$already  = $lessonId > 0 && isLessonDone($pdo, $userId, $lessonId);

if ($lessonId > 0 && !$already) {
    $pdo->prepare(
        'INSERT IGNORE INTO lesson_completions (user_id, lesson_id, completed_at) VALUES (?,?,NOW())'
    )->execute([$userId, $lessonId]);

    $pdo->prepare(
        'INSERT INTO lesson_quiz_results (user_id, lesson_id, score, passed, completed_at)
         VALUES (?,?,100,1,NOW())
         ON DUPLICATE KEY UPDATE score=100, passed=1, completed_at=NOW()'
    )->execute([$userId, $lessonId]);

}

$xpGained = 0;
if (!$already) {
    $xpGained = $xpReward;
    addUserXp($pdo, $userId, $xpReward);
} else {
    syncSessionUserXp($pdo, $userId);
}

$totalXp = getUserXp($pdo, $userId);
$badge   = $v4['badge'] ?? $course['badge'] ?? null;

$_SESSION['v4_complete'] = [
    'course_id' => $courseId,
    'title'     => $v4['title'] ?? $course['title'],
    'xp_gained' => $xpGained,
    'badge'     => $badge,
];

$redirect = asset('complete.php?course_id=' . $courseId);

echo json_encode([
    'ok'        => true,
    'redirect'  => $redirect,
    'xp_gained' => $xpGained,
    'xp'        => $totalXp,
    'level'     => getUserLevel($totalXp),
    'badge'     => $badge,
]);
