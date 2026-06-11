<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/** Базовый URL-путь приложения (подпапка в www), без завершающего слэша. */
function computeAppBase(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docRoot = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $project = str_replace('\\', '/', (string)realpath(dirname(__DIR__)));
    if ($docRoot !== '' && $project !== '' && strpos($project, $docRoot) === 0) {
        $base = rtrim(substr($project, strlen($docRoot)), '/');
    } else {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir    = dirname($script);
        $base   = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }
    return $base;
}

/** Путь cookie для сессии и языка (корень хоста — стабильно в подпапке www). */
function cookiePath(): string
{
    return '/';
}

function initAppSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => cookiePath(),
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Сохранить сессию в cookie до отправки JSON-ответа (важно для AJAX-входа). */
function commitSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

initAppSession();

require_once __DIR__ . '/i18n.php';

function isAjaxRequest(): bool
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return str_contains($accept, 'application/json');
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
    } elseif (str_starts_with($path, '/')) {
        // Уже абсолютный путь (например после asset()) — не дублировать базу.
        header('Location: ' . $path);
    } else {
        header('Location: ' . asset($path));
    }
    exit;
}

function e(?string $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** Абсолютный URL-путь к статике от корня сайта (для UniServer / подпапки в www). */
function asset(string $path): string
{
    $base = computeAppBase();
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return ($base === '' ? '/' : $base . '/') . $path;
}

/**
 * Версионированный URL к статике (cache-busting по времени изменения файла).
 * Нужен для хостинга/браузеров, которые агрессивно кешируют CSS/JS.
 */
function assetv(string $path): string
{
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
    $url = asset($cleanPath);
    $full = dirname(__DIR__) . '/' . $cleanPath;
    if (!is_file($full)) {
        return $url;
    }
    $v = (string)@filemtime($full);
    return $url . (str_contains($url, '?') ? '&v=' : '?v=') . $v;
}

/** URL картинки героя, если файл есть в проекте. */
function heroImageUrl(): string
{
    static $url = null;
    if ($url !== null) {
        return $url;
    }
    $root = dirname(__DIR__);
    foreach (['img/finya-hero.svg', 'img/finya-hero.png', 'public/img/finya-hero.png'] as $rel) {
        if (is_file($root . '/' . $rel)) {
            $url = asset($rel);
            return $url;
        }
    }
    $url = '';
    return $url;
}

/** Иллюстрация пробного урока (абсолютный путь для хостинга / подпапки). */
function trialIntroImageUrl(): string
{
    static $url = null;
    if ($url !== null) {
        return $url;
    }
    $root = dirname(__DIR__);
    foreach (['img/trial/money-intro.png', 'img/trial/money-intro.webp', 'img/courses/money/lesson-1.png'] as $rel) {
        if (is_file($root . '/' . $rel)) {
            $url = asset($rel);
            return $url;
        }
    }
    $url = asset('img/courses/lesson-default.svg');
    return $url;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if (isAjaxRequest()) {
            jsonResponse([
                'ok'      => false,
                'error'   => 'csrf',
                'message' => __('auth.err_csrf'),
            ], 403);
        }
        die(__('auth.err_csrf'));
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function courseProgress(PDO $pdo, int $userId, int $courseId): array
{
    $lessons = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $lessons->execute([$courseId]);
    $total = (int)$lessons->fetchColumn();

    $done = $pdo->prepare(
        'SELECT COUNT(*) FROM lesson_completions lc
         JOIN lessons l ON l.id = lc.lesson_id
         WHERE lc.user_id = ? AND l.course_id = ?'
    );
    $done->execute([$userId, $courseId]);
    $completed = (int)$done->fetchColumn();

    $prog = $pdo->prepare('SELECT * FROM user_progress WHERE user_id = ? AND course_id = ? LIMIT 1');
    $prog->execute([$userId, $courseId]);
    $row = $prog->fetch() ?: ['test_passed' => 0, 'test_score' => 0];

    return [
        'total_lessons'     => $total,
        'completed_lessons' => $completed,
        'all_lessons_done'  => $total > 0 && $completed >= $total,
        'test_passed'       => (int)($row['test_passed'] ?? 0),
        'test_score'        => (float)($row['test_score'] ?? 0),
    ];
}

function isLessonDone(PDO $pdo, int $userId, int $lessonId): bool
{
    $st = $pdo->prepare('SELECT 1 FROM lesson_completions WHERE user_id = ? AND lesson_id = ? LIMIT 1');
    $st->execute([$userId, $lessonId]);
    return (bool)$st->fetch();
}

function getLessonQuestions(PDO $pdo, int $lessonId): array
{
    $st = $pdo->prepare('SELECT * FROM questions WHERE lesson_id = ? ORDER BY sort_order, id');
    $st->execute([$lessonId]);
    return $st->fetchAll();
}

function getFinalQuestions(PDO $pdo, int $courseId): array
{
    $st = $pdo->prepare('SELECT * FROM questions WHERE course_id = ? AND lesson_id IS NULL ORDER BY sort_order, id');
    $st->execute([$courseId]);
    return $st->fetchAll();
}

function isLessonQuizPassed(PDO $pdo, int $userId, int $lessonId): bool
{
    $questions = getLessonQuestions($pdo, $lessonId);
    if (count($questions) === 0) {
        return true;
    }
    $st = $pdo->prepare('SELECT passed FROM lesson_quiz_results WHERE user_id = ? AND lesson_id = ? LIMIT 1');
    $st->execute([$userId, $lessonId]);
    $row = $st->fetch();
    return $row && (int)$row['passed'] === 1;
}

function checkQuestionAnswer(array $q, $userAnswer): bool
{
    $type = $q['type'];
    if ($type === 'open') {
        $expected = mb_strtolower(trim((string)$q['correct_open']));
        $given    = mb_strtolower(trim((string)$userAnswer));
        return $expected !== '' && ($given === $expected || strpos($given, $expected) !== false);
    }
    if ($type === 'multiple') {
        $correct = array_map('trim', explode(',', strtoupper((string)$q['correct_answer'])));
        sort($correct);
        $given = is_array($userAnswer) ? $userAnswer : explode(',', (string)$userAnswer);
        $given = array_map(function ($x) {
            return strtoupper(trim((string)$x));
        }, $given);
        sort($given);
        return $correct === $given;
    }
    return strtoupper(trim((string)$userAnswer)) === strtoupper(trim((string)$q['correct_answer']));
}

function gradeAnswers(PDO $pdo, array $questions, int $userId, array $postAnswers): array
{
    $correct = 0;
    $total   = count($questions);
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        if ($q['type'] === 'multiple') {
            $ans = $postAnswers[$qid] ?? [];
            if (!is_array($ans)) {
                $ans = [$ans];
            }
        } else {
            $ans = $postAnswers[$qid] ?? '';
        }
        $ok = checkQuestionAnswer($q, $ans);
        if ($ok) {
            $correct++;
        }
        $pdo->prepare(
            'INSERT INTO test_answers (user_id, question_id, answer_text, is_correct) VALUES (?,?,?,?)'
        )->execute([
            $userId,
            $qid,
            is_array($ans) ? implode(',', $ans) : (string)$ans,
            $ok ? 1 : 0,
        ]);
    }
    $score = $total > 0 ? round(100 * $correct / $total, 1) : 0;
    return ['score' => $score, 'passed' => $score >= PASS_PERCENT, 'correct' => $correct, 'total' => $total];
}

function getUserXp(PDO $pdo, int $userId): int
{
    static $hasColumn = null;
    if ($hasColumn === null) {
        try {
            $pdo->query('SELECT xp FROM users LIMIT 0');
            $hasColumn = true;
        } catch (PDOException $e) {
            $hasColumn = false;
        }
    }
    if (!$hasColumn) {
        return (int)($_SESSION['user']['xp'] ?? 0);
    }
    $st = $pdo->prepare('SELECT xp FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    return (int)($st->fetchColumn() ?: 0);
}

function addUserXp(PDO $pdo, int $userId, int $amount): int
{
    if ($amount < 1) {
        return getUserXp($pdo, $userId);
    }
    try {
        $pdo->prepare('UPDATE users SET xp = xp + ? WHERE id = ?')->execute([$amount, $userId]);
    } catch (PDOException $e) {
        $_SESSION['user']['xp'] = (int)($_SESSION['user']['xp'] ?? 0) + $amount;
        return (int)$_SESSION['user']['xp'];
    }
    $xp = getUserXp($pdo, $userId);
    if (isset($_SESSION['user'])) {
        $_SESSION['user']['xp'] = $xp;
    }
    return $xp;
}

function getUserLevel(int $xp): int
{
    return (int)floor($xp / 100) + 1;
}

function syncSessionUserXp(PDO $pdo, int $userId): void
{
    if (!isset($_SESSION['user'])) {
        return;
    }
    $_SESSION['user']['xp'] = getUserXp($pdo, $userId);
}

function generateCertificateFile(PDO $pdo, int $userId, int $courseId): ?int
{
    $user = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
    $user->execute([$userId]);
    $u = $user->fetch();
    $course = $pdo->prepare('SELECT title FROM courses WHERE id = ?');
    $course->execute([$courseId]);
    $c = $course->fetch();
    if (!$u || !$c) {
        return null;
    }

    $exists = $pdo->prepare('SELECT id FROM certificates WHERE user_id = ? AND course_id = ? LIMIT 1');
    $exists->execute([$userId, $courseId]);
    if ($row = $exists->fetch()) {
        return (int)$row['id'];
    }

    $code = 'FK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $dir  = dirname(__DIR__) . '/certificates';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fileName = 'cert_' . $userId . '_' . $courseId . '_' . time() . '.pdf';
    $path     = $dir . '/' . $fileName;
    $date     = date('d.m.Y');

    require_once __DIR__ . '/certificate.php';
    $html = buildCertificateHtml($u['name'], $c['title'], $date, $code);
    if (!generateCertificatePdf($html, $path)) {
        require_once __DIR__ . '/mail.php';
        mailLog('generateCertificateFile: не удалось создать PDF для user ' . $userId);
        return null;
    }

    $pdo->prepare(
        'INSERT INTO certificates (user_id, course_id, code, file_path, issued_at) VALUES (?,?,?,?,NOW())'
    )->execute([$userId, $courseId, $code, 'certificates/' . $fileName]);

    $certId = (int)$pdo->lastInsertId();
    require_once __DIR__ . '/mail.php';
    sendCertificateEmail($u['email'], $u['name'], $c['title'], $path, $code);

    return $certId;
}
