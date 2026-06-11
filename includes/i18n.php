<?php
declare(strict_types=1);

function currentLang(): string
{
    $allowed = ['ru', 'kz', 'en'];
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed, true)) {
        return $_SESSION['lang'];
    }
    if (!empty($_COOKIE['finkid_lang']) && in_array($_COOKIE['finkid_lang'], $allowed, true)) {
        $_SESSION['lang'] = $_COOKIE['finkid_lang'];
        return $_COOKIE['finkid_lang'];
    }
    return 'ru';
}

function loadTranslations(): array
{
    static $cache = [];
    $lang = currentLang();
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
    if (!is_file($file)) {
        $file = dirname(__DIR__) . '/lang/ru.php';
    }
    $cache[$lang] = require $file;
    return $cache[$lang];
}

/** Перевод ключа. */
function __(string $key): string
{
    $t = loadTranslations();
    return $t[$key] ?? $key;
}

function e_t(string $key): string
{
    return e(__($key));
}

function __f(string $key, ...$args): string
{
    return sprintf(__($key), ...$args);
}

/** Атрибуты data-i18n + data-i18n-args для клиентского переключения языка. */
function i18n_attrs(string $key, array $args = []): string
{
    $s = ' data-i18n="' . e($key) . '"';
    if ($args !== []) {
        $json = json_encode(array_values($args), JSON_UNESCAPED_UNICODE);
        $s .= ' data-i18n-args="' . e($json !== false ? $json : '[]') . '"';
    }
    return $s;
}

/** Базовый URL-путь приложения (для cookie). */
function appBasePath(): string
{
    return cookiePath();
}

/** Язык из POST (формы входа/регистрации) — синхронизировать с выбором в UI. */
function applyLangFromRequest(): void
{
    $lang = $_POST['lang'] ?? '';
    if (in_array($lang, ['ru', 'kz', 'en'], true)) {
        persistLang($lang);
    }
}

/** Все языковые пакеты для клиентского переключения без перезагрузки. */
function getAllTranslationPacks(): array
{
    $packs = [];
    foreach (['ru', 'kz', 'en'] as $code) {
        $file = dirname(__DIR__) . '/lang/' . $code . '.php';
        $packs[$code] = is_file($file) ? require $file : [];
    }
    return $packs;
}

/** Метаданные курсов платформы (название/описание) для всех языков — sidebar, карточки. */
function getCourseMetaForI18n(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $meta = [];
    foreach (['ru', 'kz', 'en'] as $code) {
        $catFile = __DIR__ . '/v4_catalog_' . $code . '.php';
        $langFile = dirname(__DIR__) . '/lang/' . $code . '.php';
        $lang = is_file($langFile) ? require $langFile : [];
        $cat = is_file($catFile) ? require $catFile : [];
        foreach ($cat as $slug => $data) {
            $descKey = 'course.desc.' . $slug;
            $meta[$slug][$code] = [
                'title' => (string) ($data['title'] ?? ''),
                'desc'  => (string) ($lang[$descKey] ?? ''),
            ];
        }
    }
    $cache = $meta;
    return $cache;
}

/** Локализованное название курса (каталог v4 или поле из БД). */
function courseLocalizedTitle(array $course): string
{
    if (!function_exists('courseSlug')) {
        require_once __DIR__ . '/v4_courses.php';
    }
    $slug = courseSlug($course);
    if ($slug !== '' && hasV4Course($course)) {
        $cat = getV4CoursesCatalog();
        if (isset($cat[$slug]['title'])) {
            return (string) $cat[$slug]['title'];
        }
    }
    return (string) ($course['title'] ?? '');
}

/** Локализованное краткое описание курса. */
function courseLocalizedDescription(array $course): string
{
    if (!function_exists('courseSlug')) {
        require_once __DIR__ . '/v4_courses.php';
    }
    $slug = courseSlug($course);
    if ($slug !== '' && hasV4Course($course)) {
        $key = 'course.desc.' . $slug;
        $t = __($key);
        if ($t !== $key) {
            return $t;
        }
    }
    return (string) ($course['description'] ?? '');
}

function persistLang(string $lang): void
{
    if (!in_array($lang, ['ru', 'kz', 'en'], true)) {
        $lang = 'ru';
    }
    $_SESSION['lang'] = $lang;
    setcookie('finkid_lang', $lang, [
        'expires'  => time() + 86400 * 365,
        'path'     => cookiePath(),
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}
