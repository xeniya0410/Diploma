<?php
declare(strict_types=1);

/** Добавляет недостающие колонки в lessons/courses (первая синхронизация со старой установкой без полного импорта schema.sql). */
function ensureCoursesTableSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $addColumn = static function (PDO $pdo, string $column, string $sql): void {
        if (dbColumnExistsTeacher($pdo, 'courses', $column)) {
            return;
        }
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // колонка могла появиться параллельно
        }
    };

    $addColumn($pdo, 'slug', 'ALTER TABLE courses ADD COLUMN slug VARCHAR(50) NULL');
    $addColumn($pdo, 'xp_reward', 'ALTER TABLE courses ADD COLUMN xp_reward INT UNSIGNED NOT NULL DEFAULT 30');
    $addColumn($pdo, 'badge', 'ALTER TABLE courses ADD COLUMN badge VARCHAR(120) NULL');
    $addColumn($pdo, 'has_final_test', 'ALTER TABLE courses ADD COLUMN has_final_test TINYINT(1) NOT NULL DEFAULT 1');

    if (!dbColumnExistsTeacher($pdo, 'courses', 'created_by')) {
        $after = '';
        foreach (['badge', 'xp_reward', 'slug', 'sort_order', 'is_free'] as $refCol) {
            if (dbColumnExistsTeacher($pdo, 'courses', $refCol)) {
                $after = ' AFTER `' . $refCol . '`';
                break;
            }
        }
        try {
            $pdo->exec(
                'ALTER TABLE courses ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL' . $after
            );
        } catch (PDOException $e) {
            try {
                $pdo->exec('ALTER TABLE courses ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL');
            } catch (PDOException $e2) {
                // уже есть
            }
        }
        try {
            $pdo->exec(
                'ALTER TABLE courses ADD CONSTRAINT fk_courses_created_by
                 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL'
            );
        } catch (PDOException $e) {
            // ограничение уже есть
        }
    }

    $checked = true;
}

function ensureTeacherCoursesSchema(PDO $pdo): void
{
    ensureCoursesTableSchema($pdo);
    $addLessonColumn = static function (PDO $pdo, string $column, string $sql): void {
        if (dbColumnExistsTeacher($pdo, 'lessons', $column)) {
            return;
        }
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) { /* */
        }
    };
    $addLessonColumn($pdo, 'illustration', 'ALTER TABLE lessons ADD COLUMN illustration VARCHAR(255) NULL');
    $addLessonColumn($pdo, 'content_html', 'ALTER TABLE lessons ADD COLUMN content_html MEDIUMTEXT NULL');

    backfillLessonIllustrations($pdo);
    repairLessonIllustrationPaths($pdo);
}

function backfillLessonIllustrations(PDO $pdo): void
{
    static $done = false;
    if ($done || !dbColumnExistsTeacher($pdo, 'lessons', 'illustration')) {
        return;
    }
    $done = true;
    if (!function_exists('defaultLessonIllustrationRel')) {
        require_once __DIR__ . '/v4_courses.php';
    }

    $st = $pdo->query(
        'SELECT l.id, l.course_id, l.sort_order, l.illustration, c.slug, c.id AS cid
         FROM lessons l INNER JOIN courses c ON c.id = l.course_id
         WHERE l.illustration IS NULL OR l.illustration = \'\''
    );
    $upd = $pdo->prepare('UPDATE lessons SET illustration = ? WHERE id = ?');
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $course = [
            'id' => (int) $row['course_id'],
            'slug' => $row['slug'] ?? '',
        ];
        $num = max(1, (int) $row['sort_order']);
        $path = defaultLessonIllustrationRel($course, $num);
        $upd->execute([$path, (int) $row['id']]);
    }
}

/** Заменить пути .svg на .png в БД, если растровый файл уже есть на диске. */
function repairLessonIllustrationPaths(PDO $pdo): void
{
    static $done = false;
    if ($done || !dbColumnExistsTeacher($pdo, 'lessons', 'illustration')) {
        return;
    }
    $done = true;
    if (!function_exists('resolveLessonIllustrationRel')) {
        require_once __DIR__ . '/v4_courses.php';
    }

    $st = $pdo->query(
        'SELECT l.id, l.course_id, l.sort_order, l.illustration, c.slug
         FROM lessons l INNER JOIN courses c ON c.id = l.course_id'
    );
    $upd = $pdo->prepare('UPDATE lessons SET illustration = ? WHERE id = ?');
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $course = [
            'id' => (int) $row['course_id'],
            'slug' => $row['slug'] ?? '',
        ];
        $num = max(1, (int) $row['sort_order']);
        $resolved = resolveLessonIllustrationRel($course, $num, $row['illustration'] ?? null);
        if ($resolved !== null && $resolved !== ($row['illustration'] ?? '')) {
            $upd->execute([$resolved, (int) $row['id']]);
        }
    }
}

function dbColumnExistsTeacher(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $column]);

    return (int) $st->fetchColumn() > 0;
}

function canTeacherEditCourse(array $course, array $user): bool
{
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    if (($user['role'] ?? '') !== 'teacher' || ($user['teacher_status'] ?? '') !== 'approved') {
        return false;
    }
    $owner = isset($course['created_by']) ? (int) $course['created_by'] : 0;

    return $owner > 0 && $owner === (int) ($user['id'] ?? 0);
}

function canTeacherViewCourseStats(array $course, array $user): bool
{
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    if (($user['role'] ?? '') !== 'teacher' || ($user['teacher_status'] ?? '') !== 'approved') {
        return false;
    }
    return true;
}

/** @return array<string, mixed>|null */
function fetchCourseRow(PDO $pdo, int $courseId): ?array
{
    $st = $pdo->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
    $st->execute([$courseId]);
    $row = $st->fetch();

    return $row ?: null;
}

function makeCourseSlug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $slug = mb_strtolower(trim($title), 'UTF-8');
    $slug = preg_replace('/[^a-z0-9а-яё]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    if ($slug === '' || mb_strlen($slug) < 2) {
        $slug = 'course-' . bin2hex(random_bytes(3));
    }
    $slug = mb_substr($slug, 0, 48);
    $candidate = $slug;
    $n = 2;
    while (courseSlugExists($pdo, $candidate, $excludeId)) {
        $candidate = $slug . '-' . $n;
        $n++;
    }

    return $candidate;
}

function courseSlugExists(PDO $pdo, string $slug, ?int $excludeId): bool
{
    if ($excludeId) {
        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? AND id <> ? LIMIT 1');
        $st->execute([$slug, $excludeId]);
    } else {
        $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
    }

    return (bool) $st->fetch();
}

/**
 * @return array{
 *   total_lessons: int,
 *   started: int,
 *   completed: int,
 *   completion_rate: float,
 *   avg_progress: float
 * }
 */
function getCourseAggregateStats(PDO $pdo, int $courseId): array
{
    $stLessons = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $stLessons->execute([$courseId]);
    $totalLessons = (int) $stLessons->fetchColumn();

    $stStarted = $pdo->prepare(
        'SELECT COUNT(DISTINCT u.id) FROM users u
         WHERE u.role = \'student\'
           AND (
             EXISTS (
               SELECT 1 FROM lesson_completions lc
               INNER JOIN lessons l ON l.id = lc.lesson_id
               WHERE lc.user_id = u.id AND l.course_id = ?
             )
             OR EXISTS (
               SELECT 1 FROM user_progress up
               WHERE up.user_id = u.id AND up.course_id = ?
             )
           )'
    );
    $stStarted->execute([$courseId, $courseId]);
    $started = (int) $stStarted->fetchColumn();

    $stCompleted = $pdo->prepare(
        'SELECT COUNT(DISTINCT u.id) FROM users u
         WHERE u.role = \'student\'
           AND (
             EXISTS (
               SELECT 1 FROM user_progress up
               WHERE up.user_id = u.id AND up.course_id = ? AND up.test_passed = 1
             )
             OR (
               ? > 0 AND (
                 SELECT COUNT(*) FROM lesson_completions lc
                 INNER JOIN lessons l ON l.id = lc.lesson_id
                 WHERE lc.user_id = u.id AND l.course_id = ?
               ) >= ?
             )
           )'
    );
    $stCompleted->execute([$courseId, $totalLessons, $courseId, $totalLessons]);
    $completed = (int) $stCompleted->fetchColumn();

    $avgProgress = 0.0;
    if ($started > 0 && $totalLessons > 0) {
        $stStudents = $pdo->prepare(
            'SELECT DISTINCT u.id FROM users u
             WHERE u.role = \'student\'
               AND (
                 EXISTS (
                   SELECT 1 FROM lesson_completions lc
                   INNER JOIN lessons l ON l.id = lc.lesson_id
                   WHERE lc.user_id = u.id AND l.course_id = ?
                 )
                 OR EXISTS (
                   SELECT 1 FROM user_progress up
                   WHERE up.user_id = u.id AND up.course_id = ?
                 )
               )'
        );
        $stStudents->execute([$courseId, $courseId]);
        $sum = 0;
        $cnt = 0;
        while ($row = $stStudents->fetch(PDO::FETCH_ASSOC)) {
            $prog = courseProgress($pdo, (int) $row['id'], $courseId);
            $sum += ($prog['completed_lessons'] / $totalLessons) * 100;
            $cnt++;
        }
        $avgProgress = $cnt > 0 ? round($sum / $cnt, 1) : 0.0;
    }

    $completionRate = $started > 0 ? round(100 * $completed / $started, 1) : 0.0;

    return [
        'total_lessons' => $totalLessons,
        'started' => $started,
        'completed' => $completed,
        'completion_rate' => $completionRate,
        'avg_progress' => $avgProgress,
    ];
}

/** @return list<array<string, mixed>> */
function getCourseStudentProgressList(PDO $pdo, int $courseId): array
{
    $stats = getCourseAggregateStats($pdo, $courseId);
    $total = $stats['total_lessons'];

    $st = $pdo->prepare(
        'SELECT DISTINCT u.id, u.name, u.email
         FROM users u
         WHERE u.role = \'student\'
           AND (
             EXISTS (
               SELECT 1 FROM lesson_completions lc
               INNER JOIN lessons l ON l.id = lc.lesson_id
               WHERE lc.user_id = u.id AND l.course_id = ?
             )
             OR EXISTS (
               SELECT 1 FROM user_progress up
               WHERE up.user_id = u.id AND up.course_id = ?
             )
           )
         ORDER BY u.name'
    );
    $st->execute([$courseId, $courseId]);

    $rows = [];
    while ($u = $st->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int) $u['id'];
        $prog = courseProgress($pdo, $uid, $courseId);
        $pct = $total > 0 ? round(100 * $prog['completed_lessons'] / $total, 1) : 0.0;
        $done = $prog['test_passed'] === 1
            || ($total > 0 && $prog['completed_lessons'] >= $total);

        $rows[] = [
            'id' => $uid,
            'name' => $u['name'],
            'email' => $u['email'],
            'completed_lessons' => $prog['completed_lessons'],
            'total_lessons' => $total,
            'progress_percent' => $pct,
            'test_passed' => (int) $prog['test_passed'],
            'test_score' => (float) $prog['test_score'],
            'is_completed' => $done,
        ];
    }

    return $rows;
}

/** @return list<array<string, mixed>> */
function listCoursesForTeacher(PDO $pdo, array $user): array
{
    ensureTeacherCoursesSchema($pdo);
    $courses = $pdo->query('SELECT * FROM courses ORDER BY sort_order, id')->fetchAll();
    $out = [];

    foreach ($courses as $c) {
        $id = (int) $c['id'];
        $stats = getCourseAggregateStats($pdo, $id);
        $out[] = array_merge($c, $stats, [
            'can_edit' => canTeacherEditCourse($c, $user),
        ]);
    }

    return $out;
}

/** @return array{enabled: bool, question: string, options: array{a: string, b: string, c: string}, correct: string} */
function lessonQuizEditorDefaults(): array
{
    return [
        'enabled' => false,
        'question' => '',
        'options' => ['a' => '', 'b' => '', 'c' => ''],
        'correct' => 'a',
    ];
}

/** @param array<string, mixed>|null $questionRow */
function lessonQuizFromQuestionRow(?array $questionRow): array
{
    $defaults = lessonQuizEditorDefaults();
    if ($questionRow === null) {
        return $defaults;
    }
    $opts = [];
    if (!empty($questionRow['options_json'])) {
        $decoded = json_decode((string) $questionRow['options_json'], true);
        if (is_array($decoded)) {
            $opts = $decoded;
        }
    }

    return [
        'enabled' => true,
        'question' => (string) ($questionRow['question_text'] ?? ''),
        'options' => [
            'a' => (string) ($opts['a'] ?? ''),
            'b' => (string) ($opts['b'] ?? ''),
            'c' => (string) ($opts['c'] ?? ''),
        ],
        'correct' => strtolower(trim((string) ($questionRow['correct_answer'] ?? 'a'))) ?: 'a',
    ];
}

/** @return list<array<string, mixed>> */
function fetchCourseLessonsForEditor(PDO $pdo, int $courseId): array
{
    if (!function_exists('getLessonQuestions')) {
        require_once __DIR__ . '/functions.php';
    }

    $lessons = fetchCourseLessons($pdo, $courseId);
    foreach ($lessons as &$lesson) {
        $lessonId = (int) ($lesson['id'] ?? 0);
        $questions = $lessonId > 0 ? getLessonQuestions($pdo, $lessonId) : [];
        $lesson['quiz'] = lessonQuizFromQuestionRow($questions[0] ?? null);
        $lesson['illustration'] = (string) ($lesson['illustration'] ?? '');
    }
    unset($lesson);

    return $lessons;
}

/**
 * @param array<int, array<string, mixed>>|null $fileList normalized $_FILES['lesson_image']
 * @return array<int, array<string, mixed>|null>
 */
function normalizeLessonImageUploads(?array $fileList): array
{
    if ($fileList === null || !isset($fileList['name']) || !is_array($fileList['name'])) {
        return [];
    }
    $out = [];
    foreach ($fileList['name'] as $i => $name) {
        if ($name === '' || ($fileList['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $out[$i] = null;
            continue;
        }
        $out[$i] = [
            'name' => (string) $name,
            'type' => (string) ($fileList['type'][$i] ?? ''),
            'tmp_name' => (string) ($fileList['tmp_name'][$i] ?? ''),
            'error' => (int) ($fileList['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($fileList['size'][$i] ?? 0),
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $course
 * @param array<string, mixed>|null $file
 */
function saveLessonIllustrationUpload(array $course, int $lessonNum, ?array $file, ?string $keepRel = null): string
{
    if (!function_exists('defaultLessonIllustrationRel')) {
        require_once __DIR__ . '/v4_courses.php';
    }

    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $keepRel = trim((string) $keepRel);
        if ($keepRel !== '') {
            $found = findProjectFile($keepRel);
            if ($found !== null) {
                return $found;
            }
        }

        return defaultLessonIllustrationRel($course, $lessonNum);
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('upload');
    }

    $maxBytes = 3 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('upload_size');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('upload');
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('upload_type');
    }

    $mime = $info['mime'] ?? '';
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => throw new RuntimeException('upload_type'),
    };

    $slug = courseSlug($course);
    if ($slug === '') {
        $slug = 'course-' . (int) ($course['id'] ?? 0);
    }

    $root = projectRoot();
    $dir = $root . '/img/courses/' . $slug;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('upload');
    }

    $rel = 'img/courses/' . $slug . '/lesson-' . $lessonNum . '.' . $ext;
    $dest = $root . '/' . $rel;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('upload');
    }

    return $rel;
}

/** @param array{enabled?: bool, question?: string, options?: array<string, string>, correct?: string} $quiz */
function syncLessonQuiz(PDO $pdo, int $courseId, int $lessonId, array $quiz): void
{
    $pdo->prepare('DELETE FROM questions WHERE lesson_id = ?')->execute([$lessonId]);

    if (empty($quiz['enabled'])) {
        return;
    }

    $qtext = trim((string) ($quiz['question'] ?? ''));
    if ($qtext === '') {
        return;
    }

    $opts = [];
    foreach (['a', 'b', 'c'] as $key) {
        $label = trim((string) ($quiz['options'][$key] ?? ''));
        if ($label !== '') {
            $opts[$key] = $label;
        }
    }
    if (count($opts) < 2) {
        return;
    }

    $correct = strtolower(trim((string) ($quiz['correct'] ?? 'a')));
    if (!isset($opts[$correct])) {
        $correct = (string) array_key_first($opts);
    }

    $pdo->prepare(
        'INSERT INTO questions (course_id, lesson_id, question_text, type, options_json, correct_answer, sort_order)
         VALUES (?, ?, ?, \'single\', ?, ?, 1)'
    )->execute([
        $courseId,
        $lessonId,
        $qtext,
        json_encode($opts, JSON_UNESCAPED_UNICODE),
        $correct,
    ]);
}

/**
 * @param array<int, array{id?: int, title?: string, content?: string, illustration_keep?: string, quiz?: array}> $lessonsInput
 * @param array<int, array<string, mixed>|null> $imageUploads
 * @return array{ok: bool, id?: int, error?: string}
 */
function saveTeacherCourse(PDO $pdo, array $user, array $data, array $lessonsInput, array $imageUploads = []): array
{
    ensureTeacherCoursesSchema($pdo);

    $courseId = (int) ($data['id'] ?? 0);
    $title = trim((string) ($data['title'] ?? ''));
    $desc = trim((string) ($data['description'] ?? ''));
    $icon = trim((string) ($data['icon'] ?? '📚')) ?: '📚';
    $isFree = !empty($data['is_free']) ? 1 : 0;
    $xp = max(0, (int) ($data['xp_reward'] ?? 30));
    $badge = trim((string) ($data['badge'] ?? '')) ?: null;
    $sort = (int) ($data['sort_order'] ?? 0);

    if ($title === '') {
        return ['ok' => false, 'error' => 'title'];
    }

    $validLessons = [];
    foreach ($lessonsInput as $row) {
        $lt = trim((string) ($row['title'] ?? ''));
        if ($lt !== '') {
            $validLessons[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => $lt,
                'content' => trim((string) ($row['content'] ?? '')),
                'illustration_keep' => trim((string) ($row['illustration_keep'] ?? '')),
                'quiz' => is_array($row['quiz'] ?? null) ? $row['quiz'] : lessonQuizEditorDefaults(),
            ];
        }
    }
    if ($validLessons === []) {
        return ['ok' => false, 'error' => 'lessons'];
    }

    if ($courseId > 0) {
        $course = fetchCourseRow($pdo, $courseId);
        if (!$course || !canTeacherEditCourse($course, $user)) {
            return ['ok' => false, 'error' => 'access'];
        }
        $slug = $course['slug'] ?? makeCourseSlug($pdo, $title, $courseId);
        $pdo->prepare(
            'UPDATE courses SET title=?, description=?, icon=?, is_free=?, sort_order=?, slug=?, xp_reward=?, badge=?
             WHERE id=?'
        )->execute([$title, $desc, $icon, $isFree, $sort, $slug, $xp, $badge, $courseId]);
    } else {
        if (($user['role'] ?? '') === 'teacher' && ($user['teacher_status'] ?? '') !== 'approved') {
            return ['ok' => false, 'error' => 'access'];
        }
        $slug = makeCourseSlug($pdo, $title);
        $ownerId = ($user['role'] ?? '') === 'admin' ? null : (int) $user['id'];
        $pdo->prepare(
            'INSERT INTO courses (title, description, icon, is_free, sort_order, slug, xp_reward, badge, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([$title, $desc, $icon, $isFree, $sort, $slug, $xp, $badge, $ownerId]);
        $courseId = (int) $pdo->lastInsertId();
        if (!courseSlugExists($pdo, 'course-' . $courseId, $courseId)) {
            $pdo->prepare('UPDATE courses SET slug = ? WHERE id = ?')
                ->execute(['course-' . $courseId, $courseId]);
        }
    }

    try {
        syncCourseLessons($pdo, $courseId, $validLessons, $imageUploads);
    } catch (RuntimeException $e) {
        $code = $e->getMessage();
        if (in_array($code, ['upload', 'upload_size', 'upload_type'], true)) {
            return ['ok' => false, 'error' => $code];
        }
        throw $e;
    }

    return ['ok' => true, 'id' => $courseId];
}

/**
 * @param list<array<string, mixed>> $lessons
 * @param array<int, array<string, mixed>|null> $imageUploads
 */
function syncCourseLessons(PDO $pdo, int $courseId, array $lessons, array $imageUploads = []): void
{
    if (!function_exists('defaultLessonIllustrationRel')) {
        require_once __DIR__ . '/v4_courses.php';
    }
    $course = fetchCourseRow($pdo, $courseId) ?? ['id' => $courseId, 'slug' => 'course-' . $courseId];
    $hasIllustration = dbColumnExistsTeacher($pdo, 'lessons', 'illustration');
    $hasContentHtml = dbColumnExistsTeacher($pdo, 'lessons', 'content_html');

    $keepIds = [];
    foreach ($lessons as $idx => $row) {
        $sort = $idx + 1;
        $lessonNum = $idx + 1;
        $id = (int) ($row['id'] ?? 0);
        $content = (string) $row['content'];
        $contentHtml = $content !== ''
            ? '<p>' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</p>'
            : '';
        $illustration = null;
        if ($hasIllustration) {
            try {
                $illustration = saveLessonIllustrationUpload(
                    $course,
                    $lessonNum,
                    $imageUploads[$idx] ?? null,
                    (string) ($row['illustration_keep'] ?? '')
                );
            } catch (RuntimeException $e) {
                throw $e;
            }
        }

        if ($id > 0) {
            if ($hasIllustration && $hasContentHtml) {
                $pdo->prepare(
                    'UPDATE lessons SET title = ?, content = ?, content_html = ?, illustration = ?, sort_order = ?
                     WHERE id = ? AND course_id = ?'
                )->execute([$row['title'], $content, $contentHtml, $illustration, $sort, $id, $courseId]);
            } elseif ($hasContentHtml) {
                $pdo->prepare(
                    'UPDATE lessons SET title = ?, content = ?, content_html = ?, sort_order = ? WHERE id = ? AND course_id = ?'
                )->execute([$row['title'], $content, $contentHtml, $sort, $id, $courseId]);
            } else {
                $pdo->prepare(
                    'UPDATE lessons SET title = ?, content = ?, sort_order = ? WHERE id = ? AND course_id = ?'
                )->execute([$row['title'], $content, $sort, $id, $courseId]);
            }
            $keepIds[] = $id;
        } else {
            if ($hasIllustration && $hasContentHtml) {
                $pdo->prepare(
                    'INSERT INTO lessons (course_id, title, content, content_html, illustration, sort_order)
                     VALUES (?,?,?,?,?,?)'
                )->execute([$courseId, $row['title'], $content, $contentHtml, $illustration, $sort]);
            } elseif ($hasContentHtml) {
                $pdo->prepare(
                    'INSERT INTO lessons (course_id, title, content, content_html, sort_order) VALUES (?,?,?,?,?)'
                )->execute([$courseId, $row['title'], $content, $contentHtml, $sort]);
            } else {
                $pdo->prepare(
                    'INSERT INTO lessons (course_id, title, content, sort_order) VALUES (?,?,?,?)'
                )->execute([$courseId, $row['title'], $content, $sort]);
            }
            $keepIds[] = (int) $pdo->lastInsertId();
        }

        $lessonId = (int) end($keepIds);
        if ($lessonId > 0) {
            syncLessonQuiz($pdo, $courseId, $lessonId, is_array($row['quiz'] ?? null) ? $row['quiz'] : []);
        }
    }

    if ($keepIds === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
    $params = array_merge([$courseId], $keepIds);
    $pdo->prepare("DELETE FROM lessons WHERE course_id = ? AND id NOT IN ({$placeholders})")
        ->execute($params);
}

function isPlatformCourse(array $course): bool
{
    $owner = isset($course['created_by']) ? (int) $course['created_by'] : 0;

    return $owner <= 0;
}

function canDeleteCourse(PDO $pdo, array $user, array $course): bool
{
    if (!canTeacherEditCourse($course, $user)) {
        return false;
    }
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }

    return !isPlatformCourse($course);
}

function deleteTeacherCourse(PDO $pdo, array $user, int $courseId): bool
{
    $course = fetchCourseRow($pdo, $courseId);
    if (!$course || !canDeleteCourse($pdo, $user, $course)) {
        return false;
    }
    $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([$courseId]);

    return true;
}

/** @return list<array<string, mixed>> */
function fetchCourseLessons(PDO $pdo, int $courseId): array
{
    $st = $pdo->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order, id');
    $st->execute([$courseId]);
    $rows = $st->fetchAll();

    foreach ($rows as &$row) {
        if (trim((string) ($row['content'] ?? '')) === '' && trim((string) ($row['content_html'] ?? '')) !== '') {
            $html = (string) $row['content_html'];
            $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
            $row['content'] = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
    unset($row);

    return $rows;
}
