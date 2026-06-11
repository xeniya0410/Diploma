<?php
declare(strict_types=1);
if (!function_exists('courseSlug')) {
    require_once __DIR__ . '/v4_courses.php';
}
try {
    $sidebarCourses = $pdo->query('SELECT * FROM courses ORDER BY sort_order, id')->fetchAll();
} catch (PDOException $e) {
    $sidebarCourses = [];
}
$sidebarAuth    = isAuth();
$sidebarUser    = $sidebarAuth ? currentUser() : null;
$iconClasses    = ['si-teal', 'si-yellow', 'si-purple', 'si-coral', 'si-green'];
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">🦊 <span data-i18n="site.name"><?= e_t('site.name') ?></span></div>
    <button type="button" class="sidebar-close" onclick="toggleSidebar()" aria-label="<?= e_t('nav.close') ?>" data-i18n-aria="nav.close">✕</button>
  </div>
  <p class="sidebar-section-title">📚 <span data-i18n="nav.courses"><?= e_t('nav.courses') ?></span></p>
  <?php foreach ($sidebarCourses as $i => $c):
    $free = (int)$c['is_free'] === 1;
    $locked = !$sidebarAuth && !$free;
    $href = $locked ? '#' : asset('course.php?id=' . (int)$c['id']);
    $onclick = $locked ? 'onclick="event.preventDefault();openModal(\'login\')"' : '';
    $slug = courseSlug($c);
    $platformI18n = hasV4Course($c);
    $courseTitle = courseLocalizedTitle($c);
  ?>
  <a href="<?= $href ?>" class="sidebar-item<?= $locked ? ' locked' : '' ?>" <?= $onclick ?>>
    <span class="si-icon <?= $iconClasses[$i % count($iconClasses)] ?>"><?= e($c['icon']) ?></span>
    <span class="si-info">
      <?php if ($platformI18n): ?>
      <span class="si-name" data-course-slug="<?= e($slug) ?>" data-course-field="title"><?= e($courseTitle) ?></span>
      <?php else: ?>
      <span class="si-name"><?= e($courseTitle) ?></span>
      <?php endif; ?>
    </span>
    <?php if ($locked): ?><span class="si-badge si-badge-lock">🔒</span><?php endif; ?>
  </a>
  <?php endforeach; ?>
  <div class="sidebar-divider"></div>
  <p class="sidebar-section-title">🎮 <span data-i18n="nav.other"><?= e_t('nav.other') ?></span></p>
  <a class="sidebar-item" href="<?= e(asset('simulator.php')) ?>">
    <span class="si-icon si-green">🐷</span>
    <span class="si-info"><span class="si-name" data-i18n="nav.simulator"><?= e_t('nav.simulator') ?></span></span>
    <span class="si-badge si-badge-demo" data-i18n="nav.demo"><?= e_t('nav.demo') ?></span>
  </a>
  <?php if (!$sidebarAuth): ?>
  <a class="sidebar-item" href="<?= e(asset('index.php')) ?>#trial">
    <span class="si-icon si-yellow">✨</span>
    <span class="si-info"><span class="si-name" data-i18n="nav.trial"><?= e_t('nav.trial') ?></span></span>
  </a>
  <?php endif; ?>
  <div class="sidebar-footer">
    <?php if ($sidebarAuth): ?>
      <a class="sf-btn" href="<?= e(asset(dashboardUrl())) ?>">👤 <?= e($sidebarUser['name'] ?? '') ?></a>
      <a class="sf-btn" href="<?= e(asset('courses.php')) ?>">📚 <span data-i18n="nav.courses"><?= e_t('nav.courses') ?></span></a>
      <?php if (($sidebarUser['role'] ?? '') === 'teacher'): ?><a class="sf-btn" href="<?= e(asset('teacher.php')) ?>">👩‍🏫 <span data-i18n="nav.teacher"><?= e_t('nav.teacher') ?></span></a><?php endif; ?>
      <?php if (($sidebarUser['role'] ?? '') === 'admin'): ?><a class="sf-btn" href="<?= e(asset('admin.php')) ?>">⚙️ <span data-i18n="nav.admin"><?= e_t('nav.admin') ?></span></a><?php endif; ?>
      <a class="sf-btn" href="<?= e(asset('logout.php')) ?>">🚪 <span data-i18n="nav.logout"><?= e_t('nav.logout') ?></span></a>
    <?php else: ?>
      <button type="button" class="sf-btn" onclick="openModal('login')">🔐 <span data-i18n="nav.login"><?= e_t('nav.login') ?></span></button>
      <button type="button" class="sf-btn" onclick="openModal('register')">🪄 <span data-i18n="nav.register"><?= e_t('nav.register') ?></span></button>
    <?php endif; ?>
    <a class="sf-btn" href="<?= e(WHATSAPP_URL) ?>" target="_blank" rel="noopener">💬 <span data-i18n="nav.whatsapp"><?= e_t('nav.whatsapp') ?></span></a>
  </div>
</div>
