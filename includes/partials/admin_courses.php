<?php
declare(strict_types=1);
/** @var list<array<string, mixed>> $adminCourses */
?>
<section class="admin-courses">
  <div class="admin-courses-head">
    <h3 class="dash-section-title" data-i18n="admin.courses_manage_title"><?= e_t('admin.courses_manage_title') ?></h3>
    <a href="<?= e(asset('teacher_course.php')) ?>" class="btn-primary btn-sm" data-i18n="admin.courses_add"><?= e_t('admin.courses_add') ?></a>
  </div>
  <p class="hint admin-courses-hint" data-i18n="admin.courses_manage_hint"><?= e_t('admin.courses_manage_hint') ?></p>

  <?php if ($adminCourses === []): ?>
    <p class="hint" data-i18n="admin.courses_empty"><?= e_t('admin.courses_empty') ?></p>
  <?php else: ?>
    <div class="admin-courses-table-wrap">
      <table class="tickets-table admin-courses-table">
        <thead>
          <tr>
            <th data-i18n="admin.courses_col_course"><?= e_t('admin.courses_col_course') ?></th>
            <th data-i18n="admin.courses_col_author"><?= e_t('admin.courses_col_author') ?></th>
            <th data-i18n="admin.courses_col_lessons"><?= e_t('admin.courses_col_lessons') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($adminCourses as $c): ?>
            <tr>
              <td>
                <span class="admin-course-icon"><?= e($c['icon'] ?? '📚') ?></span>
                <strong><?= e($c['display_title'] ?? $c['title']) ?></strong>
              </td>
              <td>
                <?php if (!empty($c['is_platform'])): ?>
                  <span class="admin-course-badge admin-course-badge--platform" data-i18n="admin.courses_author_platform"><?= e_t('admin.courses_author_platform') ?></span>
                <?php else: ?>
                  <span data-i18n="admin.courses_author_teacher"><?= e_t('admin.courses_author_teacher') ?></span>
                  <?php if (!empty($c['author_label'])): ?>
                    <br><small><?= e($c['author_label']) ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td><?= (int) ($c['lesson_count'] ?? 0) ?></td>
              <td class="admin-courses-actions-cell">
                <div class="admin-courses-actions">
                  <a href="<?= e(asset('teacher_course.php?id=' . (int) $c['id'])) ?>" class="btn-secondary btn-sm" data-i18n="admin.courses_edit"><?= e_t('admin.courses_edit') ?></a>
                  <a href="<?= e(asset('course.php?id=' . (int) $c['id'])) ?>" class="btn-secondary btn-sm" target="_blank" rel="noopener" data-i18n="admin.courses_preview"><?= e_t('admin.courses_preview') ?></a>
                  <form method="post" action="<?= e(asset('admin.php?tab=courses')) ?>" class="admin-course-delete-form"
                    onsubmit="return confirm('<?= e(addslashes(__('admin.confirm_delete_course'))) ?>')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="delete_course_id" value="<?= (int) $c['id'] ?>">
                    <button type="submit" class="btn-secondary btn-sm btn-danger" data-i18n="admin.courses_delete"><?= e_t('admin.courses_delete') ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
