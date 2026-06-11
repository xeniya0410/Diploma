<?php
declare(strict_types=1);

/**
 * Контент v4-курсов (каталог + курсы преподавателя из БД).
 */
function getV4CoursesCatalog(): array
{
    static $cache = [];
    $lang = currentLang();
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    $file = __DIR__ . '/v4_catalog_' . $lang . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/v4_catalog_ru.php';
    }
    $cache[$lang] = require $file;
    return $cache[$lang];
}

function getV4CourseBySlug(string $slug): ?array
{
    $cat = getV4CoursesCatalog();
    return $cat[$slug] ?? null;
}

/** Slug платформенного курса (money/budget/savings) — без привязки к языку UI. */
function isPlatformV4Slug(string $slug): bool
{
    if ($slug === '') {
        return false;
    }
    static $slugs = null;
    if ($slugs === null) {
        $file = __DIR__ . '/v4_catalog_ru.php';
        $cat = is_file($file) ? require $file : [];
        $slugs = array_keys($cat);
    }
    return in_array($slug, $slugs, true);
}

function platformSlugFromTitle(string $title): string
{
    static $map = [
        'деньги и ты' => 'money',
        'бюджет и расходы' => 'budget',
        'копить — это круто' => 'savings',
        'копилка мечты' => 'savings',
        'умный покупатель' => 'budget',
        'ақша және сен' => 'money',
        'бюджет және шығындар' => 'budget',
        'жинақтау — керемет' => 'savings',
        'money and you' => 'money',
        'budget and expenses' => 'budget',
        'saving is cool' => 'savings',
    ];
    $key = mb_strtolower(trim($title));
    return $map[$key] ?? '';
}

function platformSlugFromIcon(string $icon): string
{
    static $map = [
        '💰' => 'money',
        '📊' => 'budget',
        '🏦' => 'savings',
        '👛' => 'savings',
        '🛒' => 'budget',
    ];
    return $map[trim($icon)] ?? '';
}

function courseSlug(array $course): string
{
    $slug = trim((string)($course['slug'] ?? ''));
    if ($slug !== '' && isPlatformV4Slug($slug)) {
        return $slug;
    }

    $fromTitle = platformSlugFromTitle((string)($course['title'] ?? ''));
    if ($fromTitle !== '') {
        return $fromTitle;
    }

    $fromIcon = platformSlugFromIcon((string)($course['icon'] ?? ''));
    if ($fromIcon !== '') {
        return $fromIcon;
    }

    $byId = [1 => 'money', 2 => 'budget', 3 => 'savings'];
    $id = (int)($course['id'] ?? 0);
    if (isset($byId[$id]) && isPlatformV4Slug($byId[$id])) {
        return $byId[$id];
    }

    $bySort = [1 => 'money', 2 => 'budget', 3 => 'savings'];
    $sort = (int)($course['sort_order'] ?? 0);
    if (isset($bySort[$sort]) && isPlatformV4Slug($bySort[$sort])) {
        return $bySort[$sort];
    }

    return '';
}

function hasV4Course(array $course): bool
{
    $slug = courseSlug($course);
    return $slug !== '' && getV4CourseBySlug($slug) !== null;
}

/** v4-интерфейс для платформенных и пользовательских курсов с уроками. */
function courseUsesV4Ui(array $course, array $dbLessons): bool
{
    return hasV4Course($course) || count($dbLessons) > 0;
}

function projectRoot(): string
{
    return dirname(__DIR__);
}

/** Найти файл в проекте (учёт регистра расширения: lesson-1.PNG на Linux). */
function findProjectFile(string $rel): ?string
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    $root = projectRoot();
    $full = $root . '/' . $rel;
    if (is_file($full)) {
        return $rel;
    }
    $dir = dirname($full);
    $base = basename($rel);
    if (!is_dir($dir)) {
        return null;
    }
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (strcasecmp($name, $base) === 0 && is_file($dir . '/' . $name)) {
            $sub = str_replace('\\', '/', substr($dir, strlen($root) + 1));

            return ($sub !== '' ? $sub . '/' : '') . $name;
        }
    }

    return null;
}

/** @return list<string> */
function lessonIllustrationCandidates(array $course, int $lessonNum): array
{
    $slug = courseSlug($course);
    $list = [];
    if ($slug !== '') {
        $base = "img/courses/{$slug}/lesson-{$lessonNum}";
        foreach (['.png', '.jpg', '.jpeg', '.webp', '.svg'] as $ext) {
            $list[] = $base . $ext;
        }
    }
    foreach (['img/courses/lesson-default.png', 'img/courses/lesson-default.svg'] as $fallback) {
        $list[] = $fallback;
    }

    return $list;
}

/** Если в БД или каталоге указан .svg, но рядом есть PNG/JPG — вернуть растровый файл. */
function resolveLessonIllustrationRel(array $course, int $lessonNum, ?string $preferredPath): ?string
{
    $preferredPath = trim((string) $preferredPath);
    if ($preferredPath !== '') {
        if (preg_match('/\.svg$/i', $preferredPath)) {
            $stem = preg_replace('/\.svg$/i', '', $preferredPath);
            foreach (['.png', '.jpg', '.jpeg', '.webp', '.PNG', '.JPG'] as $ext) {
                $found = findProjectFile($stem . $ext);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        $found = findProjectFile($preferredPath);
        if ($found !== null) {
            return $found;
        }
    }
    foreach (lessonIllustrationCandidates($course, $lessonNum) as $candidate) {
        $found = findProjectFile($candidate);
        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

/** Путь к иллюстрации урока (PNG в приоритете, SVG — запасной вариант). */
function defaultLessonIllustrationRel(array $course, int $lessonNum): string
{
    return resolveLessonIllustrationRel($course, $lessonNum, null)
        ?? 'img/courses/lesson-default.svg';
}

/** @return array{illustration: ?string, illustrationUrl: ?string} */
function lessonIllustrationFields(array $course, int $lessonNum, ?string $dbPath): array
{
    $rel = resolveLessonIllustrationRel($course, $lessonNum, $dbPath);
    if ($rel !== null) {
        return ['illustration' => $rel, 'illustrationUrl' => asset($rel)];
    }

    return ['illustration' => null, 'illustrationUrl' => null];
}

function applyLessonIllustration(array &$lesson, array $course, int $lessonNum, ?string $dbPath): void
{
    $illus = lessonIllustrationFields($course, $lessonNum, $dbPath);
    $lesson['illustration'] = $illus['illustration'];
    $lesson['illustrationUrl'] = $illus['illustrationUrl'];
}

/** Каталог v4 используется только если число уроков в БД совпадает с каталогом. */
function v4CatalogMatchesDbLessons(array $course, array $dbLessons): bool
{
    if (!hasV4Course($course)) {
        return false;
    }
    $v4 = getV4CourseBySlug(courseSlug($course));
    if ($v4 === null) {
        return false;
    }
    $catalogLessons = $v4['lessons'] ?? [];
    if ($catalogLessons !== []) {
        return count($dbLessons) === count($catalogLessons);
    }

    return count($dbLessons) <= 1;
}

/** @return array<string, mixed> */
function resolveCourseV4Payload(PDO $pdo, array $course, array $dbLessons, int $userId): array
{
    if (v4CatalogMatchesDbLessons($course, $dbLessons)) {
        $payload = buildV4CoursePayload($pdo, $course, $dbLessons, $userId);
        if ($payload !== []) {
            return $payload;
        }
    }

    return buildV4CoursePayloadFromDb($pdo, $course, $dbLessons, $userId);
}

/** Курс с привязкой id уроков из БД и флагами прогресса. */
function buildV4CoursePayload(PDO $pdo, array $course, array $dbLessons, int $userId): array
{
    $slug = courseSlug($course);
    $v4 = getV4CourseBySlug($slug);
    if (!$v4) {
        return [];
    }

    $completedIds = [];
    foreach ($dbLessons as $l) {
        if (isLessonDone($pdo, $userId, (int) $l['id'])) {
            $completedIds[] = (int) $l['id'];
        }
    }

    $prog = courseProgress($pdo, $userId, (int) $course['id']);
    $displayTitle = courseLocalizedTitle($course);

    if (!empty($v4['lessons'])) {
        $lessons = $v4['lessons'];
        foreach ($lessons as $i => &$lesson) {
            $lessonNum = $i + 1;
            $lesson['lessonId'] = (int) ($dbLessons[$i]['id'] ?? 0);
            $lesson['dbTitle'] = (string) ($dbLessons[$i]['title'] ?? $lesson['title'] ?? '');
            $dbHtml = trim((string) ($dbLessons[$i]['content_html'] ?? ''));
            if ($dbHtml !== '' && !empty($lesson['steps'][0]) && ($lesson['steps'][0]['type'] ?? '') === 'content') {
                $lesson['steps'][0]['html'] = $dbHtml;
            }
            $dbIll = $dbLessons[$i]['illustration'] ?? null;
            applyLessonIllustration($lesson, $course, $lessonNum, $dbIll !== null ? (string) $dbIll : ($lesson['illustration'] ?? null));
            $lessonId = (int) ($dbLessons[$i]['id'] ?? 0);
            if ($lessonId > 0 && !empty($lesson['steps'])) {
                $lesson['steps'] = v4MergeLessonStepsWithDbQuiz($lesson['steps'], $pdo, $lessonId);
            }
        }
        unset($lesson);
    } else {
        $lessonId = (int) ($dbLessons[0]['id'] ?? 0);
        $steps = $v4['steps'] ?? [];
        if ($lessonId > 0 && $steps !== []) {
            $steps = v4MergeLessonStepsWithDbQuiz($steps, $pdo, $lessonId);
        }
        $lesson = [
            'title' => $displayTitle,
            'lessonId' => $lessonId,
            'steps' => $steps,
            'bubbles' => $v4['bubbles'] ?? [],
        ];
        applyLessonIllustration($lesson, $course, 1, $dbLessons[0]['illustration'] ?? null);
        $lessons = [$lesson];
    }

    $totalLessons = count($lessons);
    $doneCount = 0;
    foreach ($lessons as $les) {
        if (in_array((int) $les['lessonId'], $completedIds, true)) {
            $doneCount++;
        }
    }

    return array_merge($v4, [
        'title' => $displayTitle,
        'lessons' => $lessons,
        'completedLessonIds' => $completedIds,
        'allLessonsDone' => $totalLessons > 0 && $doneCount >= $totalLessons,
        'testPassed' => (int) $prog['test_passed'] === 1,
        'hasFinalTest' => !empty($v4['has_final_test']),
    ]);
}

/**
 * Сборка payload для course-v4.js из БД (курсы преподавателя без каталога).
 *
 * @return array<string, mixed>
 */
function buildV4CoursePayloadFromDb(PDO $pdo, array $course, array $dbLessons, int $userId): array
{
    $courseId = (int) $course['id'];
    $completedIds = [];
    foreach ($dbLessons as $l) {
        if (isLessonDone($pdo, $userId, (int) $l['id'])) {
            $completedIds[] = (int) $l['id'];
        }
    }
    $prog = courseProgress($pdo, $userId, $courseId);
    $displayTitle = courseLocalizedTitle($course);

    $lessonsOut = [];
    foreach ($dbLessons as $idx => $l) {
        $lessonId = (int) $l['id'];
        $lessonNum = $idx + 1;
        $html = trim((string) ($l['content_html'] ?? ''));
        if ($html === '') {
            $plain = trim((string) ($l['content'] ?? ''));
            $html = $plain !== '' ? '<p>' . nl2br(htmlspecialchars($plain, ENT_QUOTES, 'UTF-8')) . '</p>' : '<p></p>';
        }

        $steps = [
            [
                'type' => 'content',
                'html' => $html,
                'btn' => __('course.btn_next'),
            ],
        ];

        $questions = getLessonQuestions($pdo, $lessonId);
        foreach ($questions as $q) {
            $step = v4QuizStepFromQuestion($q);
            if (($step['options'] ?? []) !== []) {
                $steps[] = $step;
            }
        }

        $lessonRow = [
            'title' => (string) $l['title'],
            'lessonId' => $lessonId,
            'steps' => $steps,
            'bubbles' => [
                __('course.bubble_1'),
                __('course.bubble_2'),
                __('course.bubble_3'),
            ],
        ];
        applyLessonIllustration($lessonRow, $course, $lessonNum, $l['illustration'] ?? null);
        $lessonsOut[] = $lessonRow;
    }

    $v4 = hasV4Course($course) ? getV4CourseBySlug(courseSlug($course)) : null;
    if ($v4 !== null && !empty($v4['lessons']) && count($v4['lessons']) === count($lessonsOut)) {
        foreach ($lessonsOut as $idx => &$row) {
            $cat = $v4['lessons'][$idx];
            if (!empty($cat['title'])) {
                $row['title'] = (string) $cat['title'];
            }
            if (!empty($cat['bubbles'])) {
                $row['bubbles'] = $cat['bubbles'];
            }
        }
        unset($row);
    }

    $hasFinal = (int) ($course['has_final_test'] ?? 1) === 1
        && count(getFinalQuestions($pdo, $courseId)) > 0;

    return [
        'title' => $displayTitle,
        'xp' => (int) ($course['xp_reward'] ?? ($v4['xp'] ?? 30)),
        'lesson_xp' => (int) ($v4['lesson_xp'] ?? 5),
        'badge' => $course['badge'] ?? ($v4['badge'] ?? null),
        'has_final_test' => $hasFinal,
        'lessons' => $lessonsOut,
        'completedLessonIds' => $completedIds,
        'allLessonsDone' => count($lessonsOut) > 0
            && count($completedIds) >= count($lessonsOut),
        'testPassed' => (int) $prog['test_passed'] === 1,
        'hasFinalTest' => $hasFinal,
    ];
}

/** @return array<string, mixed> */
function v4QuizStepFromQuestion(array $q): array
{
    $opts = [];
    if (!empty($q['options_json'])) {
        $decoded = json_decode((string) $q['options_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $key => $label) {
                $opts[] = [
                    'text' => (string) $label,
                    'correct' => v4OptionIsCorrect($q, (string) $key),
                    'feedback' => v4OptionIsCorrect($q, (string) $key)
                        ? __('course.fb_correct')
                        : __('course.fb_try_again'),
                ];
            }
        }
    }

    return [
        'type' => 'quiz',
        'question' => (string) ($q['question_text'] ?? ''),
        'options' => $opts,
    ];
}

/** @param list<array<string, mixed>> $steps */
function v4MergeLessonStepsWithDbQuiz(array $steps, PDO $pdo, int $lessonId): array
{
    if (!function_exists('getLessonQuestions')) {
        require_once __DIR__ . '/functions.php';
    }
    $questions = getLessonQuestions($pdo, $lessonId);
    if ($questions === []) {
        return $steps;
    }
    $steps = array_values(array_filter(
        $steps,
        static fn(array $s): bool => ($s['type'] ?? '') !== 'quiz'
    ));
    foreach ($questions as $q) {
        $step = v4QuizStepFromQuestion($q);
        if (($step['options'] ?? []) !== []) {
            $steps[] = $step;
        }
    }

    return $steps;
}

function v4OptionIsCorrect(array $question, string $key): bool
{
    $key = strtoupper(trim($key));
    if (($question['type'] ?? '') === 'multiple') {
        $correct = array_map('trim', explode(',', strtoupper((string) $question['correct_answer'])));
        return in_array($key, $correct, true);
    }

    return $key === strtoupper(trim((string) $question['correct_answer']));
}
