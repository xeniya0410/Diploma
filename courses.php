<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/v4_courses.php';
requireAuth();

$user = currentUser();
$courses = $pdo->query('SELECT * FROM courses ORDER BY sort_order, id')->fetchAll();

$pageTitle = __('nav.courses') . ' — ' . __('site.name');
$extraCss  = ['css/pages/panels.css'];
require __DIR__ . '/includes/header.php';
?>

<div class="container panel-page">
  <h1 class="page-title">📚 <?= e_t('nav.courses') ?></h1>
  <div class="course-grid">
    <?php foreach ($courses as $c):
      $free = (int)$c['is_free'] === 1;
      $prog = courseProgress($pdo, (int)$user['id'], (int)$c['id']);
    ?>
    <div class="app-card course-card">
      <div class="course-card__icon"><?= e($c['icon']) ?></div>
      <?php $slug = courseSlug($c); $platformI18n = hasV4Course($c); ?>
      <h3<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="title"' : '' ?>><?= e(courseLocalizedTitle($c)) ?></h3>
      <p<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="desc"' : '' ?>><?= e(courseLocalizedDescription($c)) ?></p>
      <?php if ($free): ?><span class="si-badge si-badge-free"><?= e_t('nav.free') ?></span><?php endif; ?>
      <p class="progress-text"<?= i18n_attrs('profile.lessons', [$prog['completed_lessons'], $prog['total_lessons']]) ?>><?= e(__f('profile.lessons', $prog['completed_lessons'], $prog['total_lessons'])) ?></p>
      <?php if ($prog['test_passed']): ?>
        <p class="badge-success course-card__done"><span data-i18n-prefix="✅ " data-i18n="profile.course_completed"><?= e_t('profile.course_completed') ?></span></p>
      <?php else: ?>
        <a href="<?= e(asset('course.php?id=' . (int)$c['id'])) ?>" class="btn-primary" data-i18n="course.open"><?= e_t('course.open') ?></a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
