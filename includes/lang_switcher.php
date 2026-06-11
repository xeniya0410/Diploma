<?php
declare(strict_types=1);
$cur = currentLang();
$langs = ['ru' => 'RU', 'kz' => 'KZ', 'en' => 'EN'];
?>
<div class="lang-switcher" role="navigation" aria-label="<?= e_t('lang.switch') ?>">
  <?php foreach ($langs as $code => $label): ?>
    <button type="button"
            class="lang-switcher__btn<?= $cur === $code ? ' is-active' : '' ?>"
            data-lang="<?= e($code) ?>"
            aria-pressed="<?= $cur === $code ? 'true' : 'false' ?>"><?= $label ?></button>
  <?php endforeach; ?>
</div>
