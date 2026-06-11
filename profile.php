<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/v4_courses.php';
requireAuth();

$user = currentUser();
if (($user['role'] ?? '') === 'admin') {
    redirect('admin.php');
}
if (($user['role'] ?? '') === 'teacher') {
    redirect('teacher.php');
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY sort_order, id')->fetchAll();
$certSt = $pdo->prepare(
    'SELECT c.*, co.title AS course_title FROM certificates c
     JOIN courses co ON co.id = c.course_id WHERE c.user_id = ? ORDER BY c.issued_at DESC'
);
$certSt->execute([(int)$user['id']]);
$certificates = $certSt->fetchAll();

$pageTitle = __('nav.profile') . ' — ' . __('site.name');
$extraCss  = ['css/pages/panels.css'];
require __DIR__ . '/includes/header.php';
?>

<div class="container panel-page">
  <h1 class="page-title"<?= i18n_attrs('profile.hello', [$user['name']]) ?>><?= e(__f('profile.hello', $user['name'])) ?> 👋</h1>
  <p class="page-lead"><?= e_t('auth.email') ?>: <?= e($user['email']) ?></p>

  <section class="panel-section">
    <h2 data-i18n="profile.my_courses"><?= e_t('profile.my_courses') ?></h2>
    <div class="course-grid">
      <?php foreach ($courses as $c):
        $prog = courseProgress($pdo, (int)$user['id'], (int)$c['id']);
        $pct  = $prog['total_lessons'] > 0
            ? (int)round(100 * $prog['completed_lessons'] / $prog['total_lessons'])
            : 0;
      ?>
      <div class="app-card course-card">
        <div class="course-card__icon"><?= e($c['icon']) ?></div>
        <?php $slug = courseSlug($c); $platformI18n = hasV4Course($c); ?>
        <h3<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="title"' : '' ?>><?= e(courseLocalizedTitle($c)) ?></h3>
        <p class="course-card__desc"<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="desc" data-course-max="80"' : '' ?>><?= e(mb_substr(courseLocalizedDescription($c), 0, 80)) ?></p>
        <div class="progress-bar"><div class="progress-bar__fill" style="width:<?= $pct ?>%"></div></div>
        <p class="progress-text"<?= i18n_attrs('profile.lessons', [$prog['completed_lessons'], $prog['total_lessons']]) ?>><?= e(__f('profile.lessons', $prog['completed_lessons'], $prog['total_lessons'])) ?></p>
        <?php if ($prog['test_passed']): ?>
          <p class="badge-success course-card__done"><span data-i18n-prefix="✅ " data-i18n="profile.course_completed"><?= e_t('profile.course_completed') ?></span></p>
        <?php else: ?>
          <a href="<?= e(asset('course.php?id=' . (int)$c['id'])) ?>" class="btn-primary" data-i18n="profile.continue"><?= e_t('profile.continue') ?></a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if (count($certificates) > 0): ?>
  <section class="panel-section">
    <h2 data-i18n="profile.certificates"><?= e_t('profile.certificates') ?></h2>
    <ul class="cert-list">
      <?php foreach ($certificates as $cert): ?>
      <li class="app-card">
        <strong><?= e($cert['course_title']) ?></strong>
        <span><span data-i18n="profile.code"><?= e_t('profile.code') ?></span>: <?= e($cert['code']) ?></span>
        <?php if (!empty($cert['file_path'])): ?>
        <a href="<?= e(asset($cert['file_path'])) ?>" target="_blank" rel="noopener" data-i18n="course.open"><?= e_t('course.open') ?></a>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
