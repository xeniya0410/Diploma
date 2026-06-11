<?php
declare(strict_types=1);

/**
 * SQL-агрегация для админ-dashboard (Chart.js).
 * Использует created_at / issued_at / updated_at из существующих таблиц.
 */
/** @return array<string, string> */
function adminLangPack(string $code): array
{
    static $cache = [];
    if (!isset($cache[$code])) {
        $file = dirname(__DIR__) . '/lang/' . $code . '.php';
        $cache[$code] = is_file($file) ? require $file : [];
    }
    return $cache[$code];
}

function adminT(array $pack, string $key): string
{
    return (string) ($pack[$key] ?? $key);
}

function getAdminAnalytics(PDO $pdo): array
{
    $summary = [
        'total_users'       => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'students'          => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
        'teachers'          => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
        'admins'            => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'certificates'      => (int)$pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn(),
        'completed_courses' => (int)$pdo->query('SELECT COUNT(*) FROM user_progress WHERE test_passed = 1')->fetchColumn(),
        'courses_count'     => (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
        'lessons_completed' => (int)$pdo->query('SELECT COUNT(*) FROM lesson_completions')->fetchColumn(),
        'new_today'         => (int)$pdo->query(
            'SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()'
        )->fetchColumn(),
        'new_7d'            => (int)$pdo->query(
            'SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)'
        )->fetchColumn(),
    ];

    $days = 30;
    $registrationsDaily = fetchDailyCounts(
        $pdo,
        'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM users
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . ($days - 1) . ' DAY)
         GROUP BY DATE(created_at) ORDER BY d',
        $days
    );

    $usersGrowth = buildCumulativeSeries($registrationsDaily);

    $certificatesMonthly = fetchMonthlyCounts(
        $pdo,
        'SELECT DATE_FORMAT(issued_at, "%Y-%m") AS m, COUNT(*) AS c FROM certificates
         WHERE issued_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY m ORDER BY m',
        12
    );

    $rolesRaw = [];
    $roleSt = $pdo->query(
        "SELECT role, COUNT(*) AS c FROM users GROUP BY role ORDER BY c DESC"
    );
    while ($row = $roleSt->fetch(PDO::FETCH_ASSOC)) {
        $rolesRaw[] = ['role' => (string) $row['role'], 'count' => (int) $row['c']];
    }

    $chartsI18n = buildAdminChartsI18n(
        $registrationsDaily,
        $usersGrowth,
        $certificatesMonthly,
        $rolesRaw
    );
    $progressI18n = buildAdminProgressI18n($summary);

    $cur = currentLang();

    return [
        'summary'       => $summary,
        'charts'        => $chartsI18n[$cur] ?? $chartsI18n['ru'],
        'charts_i18n'   => $chartsI18n,
        'progress'      => $progressI18n[$cur] ?? $progressI18n['ru'],
        'progress_i18n' => $progressI18n,
    ];
}

/** @param array<int, array{role: string, count: int}> $rolesRaw */
function buildAdminChartsI18n(
    array $registrationsDaily,
    array $usersGrowth,
    array $certificatesMonthly,
    array $rolesRaw
): array {
    $out = [];
    foreach (['ru', 'kz', 'en'] as $lang) {
        $pack = adminLangPack($lang);
        $roleLabels = [];
        $roleValues = [];
        foreach ($rolesRaw as $row) {
            $roleLabels[] = roleLabelForChartLang($row['role'], $pack);
            $roleValues[] = $row['count'];
        }
        $certLabels = [];
        $ymKeys = $certificatesMonthly['ym_keys'] ?? [];
        foreach ($ymKeys as $ym) {
            $certLabels[] = monthLabelShortLang($ym, $pack);
        }
        if ($certLabels === []) {
            $certLabels = $certificatesMonthly['labels'];
        }
        $out[$lang] = [
            'registrations_daily' => [
                'labels' => $registrationsDaily['labels'],
                'values' => $registrationsDaily['values'],
                'label'  => adminT($pack, 'admin.dash_chart_regs'),
            ],
            'users_growth' => [
                'labels' => $usersGrowth['labels'],
                'values' => $usersGrowth['values'],
                'label'  => adminT($pack, 'admin.dash_chart_growth'),
            ],
            'certificates_monthly' => [
                'labels' => $certLabels,
                'values' => $certificatesMonthly['values'],
                'label'  => adminT($pack, 'admin.dash_chart_certs'),
            ],
            'roles' => [
                'labels' => $roleLabels,
                'values' => $roleValues,
            ],
        ];
    }
    return $out;
}

/** @return array<string, array<int, array<string, mixed>>> */
function buildAdminProgressI18n(array $summary): array
{
    $out = [];
    foreach (['ru', 'kz', 'en'] as $lang) {
        $out[$lang] = buildProgressMetricsForLang($summary, adminLangPack($lang));
    }
    return $out;
}

function roleLabelForChartLang(string $role, array $pack): string
{
    $map = [
        'student' => 'auth.role_student',
        'teacher' => 'auth.role_teacher',
        'admin'   => 'auth.role_admin',
    ];
    $key = $map[$role] ?? $role;
    return adminT($pack, $key);
}

/** @return array{labels: string[], values: int[]} */
function fetchDailyCounts(PDO $pdo, string $sql, int $days): array
{
    $map = [];
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(string)$row['d']] = (int)$row['c'];
    }

    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = date('d.m', strtotime($date));
        $values[] = $map[$date] ?? 0;
    }

    return ['labels' => $labels, 'values' => $values];
}

/** @param array{labels: string[], values: int[]} $daily */
function buildCumulativeSeries(array $daily): array
{
    $cum   = 0;
    $values = [];
    foreach ($daily['values'] as $v) {
        $cum += $v;
        $values[] = $cum;
    }
    return ['labels' => $daily['labels'], 'values' => $values];
}

/** @return array{labels: string[], values: int[]} */
function fetchMonthlyCounts(PDO $pdo, string $sql, int $months): array
{
    $map = [];
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(string)$row['m']] = (int)$row['c'];
    }

    $labels = [];
    $values = [];
    $ymKeys = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("first day of -{$i} months"));
        $ymKeys[] = $key;
        $labels[] = monthLabelShort($key);
        $values[] = $map[$key] ?? 0;
    }

    return ['labels' => $labels, 'values' => $values, 'ym_keys' => $ymKeys];
}

function monthLabelShortLang(string $ym, array $pack): string
{
    $parts = explode('-', $ym);
    $monthNum = $parts[1] ?? '01';
    $key = 'admin.month_' . $monthNum;
    $m = adminT($pack, $key);
    return $m . ' ' . substr($parts[0] ?? '', 2);
}

function monthLabelShort(string $ym): string
{
    return monthLabelShortLang($ym, adminLangPack(currentLang()));
}

/** @param array<string, int> $summary */
function buildProgressMetricsForLang(array $summary, array $pack): array
{
    $total = max(1, $summary['total_users']);
    $students = $summary['students'];
    $courses = max(1, $summary['courses_count']);
    $potentialCompletions = max(1, $students * $courses);
    $certRate = min(100, (int) round(100 * $summary['certificates'] / $total));
    $studentShare = min(100, (int) round(100 * $students / $total));
    $courseCompletion = min(100, (int) round(100 * $summary['completed_courses'] / $potentialCompletions));
    $lessonActivity = min(100, (int) round(100 * $summary['lessons_completed'] / max(1, $students * 3)));

    return [
        [
            'key'       => 'students',
            'label_key' => 'admin.dash_progress_students',
            'value'     => $studentShare,
            'hint_num'  => $students . ' / ' . $total,
            'hint_key'  => null,
        ],
        [
            'key'       => 'courses',
            'label_key' => 'admin.dash_progress_courses',
            'value'     => $courseCompletion,
            'hint_num'  => (string) $summary['completed_courses'],
            'hint_key'  => 'admin.dash_progress_completed_hint',
        ],
        [
            'key'       => 'certs',
            'label_key' => 'admin.dash_progress_certs',
            'value'     => $certRate,
            'hint_num'  => (string) $summary['certificates'],
            'hint_key'  => 'admin.dash_progress_certs_hint',
        ],
        [
            'key'       => 'lessons',
            'label_key' => 'admin.dash_progress_lessons',
            'value'     => $lessonActivity,
            'hint_num'  => (string) $summary['lessons_completed'],
            'hint_key'  => 'admin.dash_progress_lessons_hint',
        ],
    ];
}
