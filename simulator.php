<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/simulator_events.php';

$months = getSimulatorMonths();
$labels = [];
foreach ($months as $m) {
    $ch = [];
    foreach ($m['choices'] as $c) {
        $ch[] = [
            'label' => __($c['label_key']),
            'tip'   => __($c['tip_key']),
            'money' => $c['money'],
            'mood'  => $c['mood'],
            'save'  => $c['save'],
            'debt'  => $c['debt'] ?? 0,
        ];
    }
    $labels[] = [
        'emoji' => $m['emoji'],
        'title' => __($m['title_key']),
        'text'  => __($m['text_key']),
        'choices' => $ch,
    ];
}

$pageTitle = __('sim.title') . ' — ' . __('site.name');
$bodyClass = 'app-body';
$extraCss  = ['css/pages/simulator.css'];
$extraJs   = ['js/simulator-game.js'];
require __DIR__ . '/includes/header.php';
?>

<div class="container simulator-page">
  <div class="sim-top">
    <h1 class="page-title" data-i18n="sim.title"><?= e_t('sim.title') ?></h1>
    <div class="smp"><span data-i18n="sim.month"><?= e_t('sim.month') ?></span> <span id="sim-m">1</span>/<?= count($months) ?></div>
  </div>

  <div class="app-card">
    <div class="sim-finya">
      <div class="sim-finya__row">
        <div class="sim-finya__avatar"><?php require __DIR__ . '/includes/partials/finya_svg.php'; ?></div>
        <div class="sim-finya__bubble" id="sf-bub" data-i18n="sim.bubble"><?= e_t('sim.bubble') ?></div>
      </div>
    </div>

    <div class="hrs">
      <div class="hr"><span class="hl">💵 <span data-i18n="sim.wallet"><?= e_t('sim.wallet') ?></span></span>
        <div class="hbw"><div class="hbf mo" id="bm" style="width:70%"></div></div>
        <span class="hv t" id="vm">3 500₸</span></div>
      <div class="hr"><span class="hl">😊 <span data-i18n="sim.mood"><?= e_t('sim.mood') ?></span></span>
        <div class="hbw"><div class="hbf md" id="bmo" style="width:80%"></div></div>
        <span class="hv y" id="vmo">80%</span></div>
      <div class="hr"><span class="hl">🏦 <span data-i18n="sim.savings"><?= e_t('sim.savings') ?></span></span>
        <div class="hbw"><div class="hbf sv" id="bs" style="width:30%"></div></div>
        <span class="hv b" id="vs">1 500₸</span></div>
    </div>

    <div class="ml" id="sim-mlbl"></div>
    <div class="evb" id="evbox">
      <div class="eem" id="evem">🎒</div>
      <div class="et" id="evt"></div>
      <div class="ed" id="evd"></div>
      <div class="chs" id="simchs"></div>
    </div>
    <div class="rc" id="simrc">
      <div class="rem2" id="rer"></div>
      <div class="rt" id="ret"></div>
      <div class="rs" data-i18n="sim.remember"><?= e_t('sim.remember') ?></div>
      <div class="chl" id="simch"></div>
      <button type="button" class="btn-primary" style="width:100%" id="nm-btn" data-i18n="sim.next_month"><?= e_t('sim.next_month') ?></button>
    </div>
  </div>
</div>

<script>window.SIM_MONTHS = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
