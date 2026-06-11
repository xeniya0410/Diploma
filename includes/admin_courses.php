<?php
declare(strict_types=1);

require_once __DIR__ . '/teacher_courses.php';
require_once __DIR__ . '/v4_courses.php';

/** @return list<array<string, mixed>> */
function listCoursesForAdmin(PDO $pdo): array
{
    ensureTeacherCoursesSchema($pdo);

    $rows = $pdo->query(
        'SELECT c.*, u.name AS author_name,
                (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count
         FROM courses c
         LEFT JOIN users u ON u.id = c.created_by
         ORDER BY c.sort_order ASC, c.id ASC'
    )->fetchAll();

    $out = [];
    foreach ($rows as $c) {
        $ownerId = isset($c['created_by']) ? (int) $c['created_by'] : 0;
        $slug = courseSlug($c);
        $catalog = $slug !== '' ? getV4CourseBySlug($slug) : null;
        $catalogLessons = is_array($catalog['lessons'] ?? null) ? count($catalog['lessons']) : 0;
        $lessonCount = (int) ($c['lesson_count'] ?? 0);
        $out[] = array_merge($c, [
            'lesson_count' => $lessonCount,
            'is_platform' => $ownerId <= 0,
            'author_label' => $ownerId > 0
                ? (string) ($c['author_name'] ?? '')
                : null,
            'display_title' => courseLocalizedTitle($c),
            'display_desc' => courseLocalizedDescription($c),
            'catalog_ready' => $catalog !== null
                && ($catalogLessons === 0 ? $lessonCount <= 1 : $lessonCount === $catalogLessons),
        ]);
    }

    return $out;
}
