<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isAuth()) {
  redirect(dashboardUrl());
}

$pageTitle = __('site.name') . ' — ' . __('about.lead');
$pageTitleI18n = 'site.name';
$bodyClass = 'landing-page';
$extraCss = ['css/pages/landing.css', 'css/pages/benefits-section.css', 'css/pages/trial.css', 'css/pages/faq-section.css'];
$trialApiUrl = asset('trial_submit.php');
require __DIR__ . '/includes/layout_head.php';

require __DIR__ . '/includes/sidebar.php';
?>

<header class="topbar topbar-landing">
  <div class="topbar-left">
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="<?= e_t('nav.menu') ?>"
      data-i18n-aria="nav.menu">☰</button>
    <div class="logo">🦊 <span data-i18n="site.name"><?= e_t('site.name') ?></span></div>
  </div>
  <div class="top-right">
    <?php require __DIR__ . '/includes/lang_switcher.php'; ?>
    <button type="button" class="btn-login" onclick="openModal('login')"
      data-i18n="nav.login"><?= e_t('nav.login') ?></button>
    <button type="button" class="btn-register" onclick="openModal('register')"
      data-i18n="nav.register"><?= e_t('nav.register') ?></button>
  </div>
</header>

<section class="hero hero-finya">
  <div class="hero-finya__visual">
    <?php $heroImg = heroImageUrl();
    if ($heroImg !== ''): ?>
      <img src="<?= e($heroImg) ?>" alt="<?= e_t('hero.finya_alt') ?>" class="hero-finya__img" width="480" height="520" data-i18n-aria="hero.finya_alt"
        onerror="this.style.display='none';document.getElementById('hero-fallback').style.display='flex'">
    <?php endif; ?>
    <div id="hero-fallback" class="hero-finya__fallback">🦊</div>
  </div>
  <div class="hero-finya__content">
    <h1 class="hero-finya__title"><span data-i18n="hero.hello"><?= e_t('hero.hello') ?></span><br><span
        data-i18n="hero.im_finya"><?= e_t('hero.im_finya') ?></span></h1>
    <div class="hero-finya__speech" data-i18n="hero.speech"><?= e_t('hero.speech') ?></div>
    <ul class="hero-finya__features">
      <li><span class="hf-icon hf-icon--green">📚</span><span><strong
            data-i18n="hero.f1_title"><?= e_t('hero.f1_title') ?></strong><small
            data-i18n="hero.f1_desc"><?= e_t('hero.f1_desc') ?></small></span></li>
      <li><span class="hf-icon hf-icon--yellow">🎮</span><span><strong
            data-i18n="hero.f2_title"><?= e_t('hero.f2_title') ?></strong><small
            data-i18n="hero.f2_desc"><?= e_t('hero.f2_desc') ?></small></span></li>
      <li><span class="hf-icon hf-icon--blue">🐷</span><span><strong
            data-i18n="hero.f3_title"><?= e_t('hero.f3_title') ?></strong><small
            data-i18n="hero.f3_desc"><?= e_t('hero.f3_desc') ?></small></span></li>
      <li><span class="hf-icon hf-icon--purple">🏆</span><span><strong
            data-i18n="hero.f4_title"><?= e_t('hero.f4_title') ?></strong><small
            data-i18n="hero.f4_desc"><?= e_t('hero.f4_desc') ?></small></span></li>
    </ul>
    <p class="hero-finya__cta" data-i18n="hero.cta"><?= e_t('hero.cta') ?></p>
    <div class="hero-finya__buttons">
      <button type="button" class="hero-main-btn" onclick="openModal('register')"
        data-i18n="hero.start"><?= e_t('hero.start') ?></button>
      <button type="button" class="hero-second-btn"
        onclick="document.getElementById('about').scrollIntoView({behavior:'smooth'})"
        data-i18n="hero.more"><?= e_t('hero.more') ?></button>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/partials/benefits_section.php'; ?>
<?php require __DIR__ . '/includes/trial_section.php'; ?>
<?php require __DIR__ . '/includes/guest_courses.php'; ?>
<?php require __DIR__ . '/includes/partials/faq_section.php'; ?>
<?php require __DIR__ . '/includes/site_footer.php'; ?>

<?php require __DIR__ . '/includes/modals/auth.php'; ?>
<?php
$extraJs = ['js/trial.js', 'js/faq-accordion.js'];
require __DIR__ . '/includes/layout_foot.php';
