<?php
declare(strict_types=1);
$footerYear = date('Y');
?>
<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <div class="site-footer__logo">🦊 <span data-i18n="site.name"><?= e_t('site.name') ?></span></div>
      <p class="site-footer__copy"<?= i18n_attrs('footer.copy', [$footerYear]) ?>><?= e(__f('footer.copy', $footerYear)) ?></p>
    </div>
    <div class="site-footer__benefits">
      <h3 class="site-footer__heading" data-i18n="footer.benefits_title"><?= e_t('footer.benefits_title') ?></h3>
      <ul class="site-footer__list">
        <li data-i18n="hero.f1_title"><?= e_t('hero.f1_title') ?></li>
        <li data-i18n="hero.f2_title"><?= e_t('hero.f2_title') ?></li>
        <li data-i18n="hero.f3_title"><?= e_t('hero.f3_title') ?></li>
        <li data-i18n="hero.f4_title"><?= e_t('hero.f4_title') ?></li>
      </ul>
    </div>
    <div class="site-footer__contact">
      <h3 class="site-footer__heading" data-i18n="footer.contact_title"><?= e_t('footer.contact_title') ?></h3>
      <nav class="site-footer__nav">
        <a href="<?= e(asset('support.php')) ?>" data-i18n="nav.support"><?= e_t('nav.support') ?></a>
        <a href="<?= e(asset('index.php')) ?>" data-i18n="footer.home"><?= e_t('footer.home') ?></a>
      </nav>
    </div>
  </div>
</footer>