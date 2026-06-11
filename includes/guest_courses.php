<?php
declare(strict_types=1);
if (!function_exists('courseSlug')) {
    require_once __DIR__ . '/v4_courses.php';
}
try {
    $guestCourses = $pdo->query('SELECT * FROM courses ORDER BY sort_order, id')->fetchAll();
} catch (PDOException $e) {
    $guestCourses = [];
}
$ccClasses = ['cc-t', 'cc-y', 'cc-p', 'cc-c', 'cc-g'];
?>
<section class="landing-section guest-courses">
  <div class="container">
    <h2 class="guest-courses__title" data-i18n="guest.courses_title"><?= e_t('guest.courses_title') ?></h2>
    <div class="guest-courses__grid cg">
      <?php foreach ($guestCourses as $i => $c):
        $free = (int)$c['is_free'] === 1;
        $cls = $ccClasses[$i % count($ccClasses)];
      ?>
      <div class="cc <?= $cls ?>" <?php if ($free): ?>onclick="location.href='<?= e(asset('index.php')) ?>#trial'"<?php else: ?>onclick="openModal('register')"<?php endif; ?>>
        <div class="cc-ico"><?= e($c['icon']) ?></div>
        <?php $slug = courseSlug($c); $platformI18n = hasV4Course($c); ?>
        <div class="cc-name"<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="title"' : '' ?>><?= e(courseLocalizedTitle($c)) ?></div>
        <div class="cc-sub" data-i18n="<?= $free ? 'guest.free_lesson' : 'guest.need_register' ?>"><?= $free ? e_t('guest.free_lesson') : e_t('guest.need_register') ?></div>
        <?php if (!$free): ?>
        <div class="lo"><span class="lo-lock" aria-hidden="true">🔒</span><span data-i18n="guest.register_lock"><?= e_t('guest.register_lock') ?></span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cta-box">
      <h3 data-i18n="guest.cta_title"><?= e_t('guest.cta_title') ?></h3>
      <p data-i18n="guest.cta_desc"><?= e_t('guest.cta_desc') ?></p>
      <button type="button" class="btn-w" onclick="openModal('register')" data-i18n="guest.cta_btn"><?= e_t('guest.cta_btn') ?></button>
    </div>
  </div>
</section>
