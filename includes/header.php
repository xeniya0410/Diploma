<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var array $extraCss */
$pageTitle = $pageTitle ?? __('site.name');
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? 'app-body';
$adminTopbar = $adminTopbar ?? false;
require __DIR__ . '/layout_head.php';

$user = isAuth() ? currentUser() : null;
require __DIR__ . '/sidebar.php';
?>
<?php if ($adminTopbar): ?>
  <header class="topbar topbar-admin admin-topbar">
    <div class="topbar-left">
      <button type="button" class="menu-btn menu-btn--light" onclick="toggleSidebar()"
        aria-label="<?= e_t('nav.menu') ?>">☰</button>
      <span class="admin-logo">🦊 <span data-i18n="admin.brand"><?= e_t('admin.brand') ?></span></span>
    </div>
    <div class="top-right">
      <?php require __DIR__ . '/lang_switcher.php'; ?>
      <a href="<?= e(asset('logout.php')) ?>" class="btn-secondary btn-sm admin-logout"
        data-i18n="nav.logout"><?= e_t('nav.logout') ?></a>
    </div>
  </header>
<?php else: ?>
  <header class="topbar">
    <div class="topbar-left">
      <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="<?= e_t('nav.menu') ?>">☰</button>
      <a href="<?= e(asset('index.php')) ?>" class="logo">🦊 <span
          data-i18n="site.name"><?= e_t('site.name') ?></span></a>
    </div>
    <div class="top-right">
      <?php require __DIR__ . '/lang_switcher.php'; ?>
      <?php if ($user): ?>
        <span class="xp-pill" title="<?= e_t('xp.level') ?>">⭐ <span
            id="header-xp"><?= (int) ($user['xp'] ?? 0) ?></span></span>
        <a href="<?= e(asset(dashboardUrl())) ?>" class="topbar-user">👤 <?= e($user['name'] ?? '') ?></a>
        <?php if (hasRole('teacher')): ?><a href="<?= e(asset('teacher.php')) ?>" class="btn-login topbar-nav-secondary"
            data-i18n="nav.teacher"><?= e_t('nav.teacher') ?></a><?php endif; ?>
        <?php if (hasRole('admin')): ?><a href="<?= e(asset('admin.php')) ?>" class="btn-login topbar-nav-secondary"
            data-i18n="nav.admin"><?= e_t('nav.admin') ?></a><?php endif; ?>
        <a href="<?= e(asset('logout.php')) ?>" class="btn-login" data-i18n="nav.logout"><?= e_t('nav.logout') ?></a>
      <?php else: ?>
        <a href="<?= e(asset('index.php')) ?>?auth=login" class="btn-login"
          data-i18n="nav.login"><?= e_t('nav.login') ?></a>
        <a href="<?= e(asset('index.php')) ?>?auth=register" class="btn-register"
          data-i18n="nav.register"><?= e_t('nav.register') ?></a>
      <?php endif; ?>
    </div>
  </header>
<?php endif; ?>
<main class="app-main">