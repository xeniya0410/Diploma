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
if (!$course || $lessonId < 1) {
    echo json_encode(['ok' => false, 'error' => 'course']);
    exit;
}

$slug = courseSlug($course);
$v4   = getV4CourseBySlug($slug);
if (!$v4) {
    echo json_encode(['ok' => false, 'error' => 'v4']);
    exit;
}

$already = isLessonDone($pdo, $userId, $lessonId);
$xpGain  = 0;

if (!$already) {
    $pdo->prepare(
        'INSERT IGNORE INTO lesson_completions (user_id, lesson_id, completed_at) VALUES (?,?,NOW())'
    )->execute([$userId, $lessonId]);

    $pdo->prepare(
        'INSERT INTO lesson_quiz_results (user_id, lesson_id, score, passed, completed_at)
         VALUES (?,?,100,1,NOW())
         ON DUPLICATE KEY UPDATE score=100, passed=1, completed_at=NOW()'
    )->execute([$userId, $lessonId]);

    $xpGain = (int)($v4['lesson_xp'] ?? 5);
    if ($xpGain > 0) {
        addUserXp($pdo, $userId, $xpGain);
    }
} else {
    syncSessionUserXp($pdo, $userId);
}

$lessonsSt = $pdo->prepare('SELECT id FROM lessons WHERE course_id = ? ORDER BY sort_order, id');
$lessonsSt->execute([$courseId]);
$allIds = array_map('intval', $lessonsSt->fetchAll(PDO::FETCH_COLUMN));

$doneCount = 0;
foreach ($allIds as $lid) {
    if (isLessonDone($pdo, $userId, $lid)) {
        $doneCount++;
    }
}

$allDone = count($allIds) > 0 && $doneCount >= count($allIds);
$totalXp = getUserXp($pdo, $userId);

echo json_encode([
    'ok'               => true,
    'xp_gained'        => $xpGain,
    'xp'               => $totalXp,
    'all_lessons_done' => $allDone,
    'final_test_url'   => $allDone ? asset('course.php?id=' . $courseId . '&view=final') : null,
]);
