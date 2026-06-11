<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var array $extraCss */
$pageTitle = $pageTitle ?? __('site.name');
$extraCss  = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
$lang = currentLang();
$htmlLang = $lang === 'kz' ? 'kk' : $lang;
$baseExtraCss = ['css/lang.css', 'css/finkid-ui.css', 'css/pages/site-footer.css', 'css/responsive.css'];
$allCss = array_merge($baseExtraCss, $extraCss);
?>
<!DOCTYPE html>
<html lang="<?= e($htmlLang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="stylesheet" href="https://fonts.bunny.net/css?family=fredoka-one:400|nunito:400,600,700,800,900&display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
  <?php foreach ($allCss as $css): ?>
  <link rel="stylesheet" href="<?= e(assetv($css)) ?>">
  <?php endforeach; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>
  data-set-lang-url="<?= e(asset('api/set_lang.php')) ?>"
  data-ai-url="<?= e(asset('api/ai.php')) ?>"
  data-chat-log-url="<?= e(asset('api/fini_log.php')) ?>"
  data-wa-url="<?= e(WHATSAPP_URL) ?>"
  data-wa-label="<?= e_t('ai.whatsapp_btn') ?>"
  data-csrf-token="<?= e(csrfToken()) ?>"
  <?= !empty($pageTitleI18n) ? 'data-title-i18n="' . e($pageTitleI18n) . '"' : '' ?>
  <?= !empty($trialApiUrl) ? 'data-trial-url="' . e($trialApiUrl) . '"' : '' ?>>
<?php require __DIR__ . '/i18n_script.php'; ?>
