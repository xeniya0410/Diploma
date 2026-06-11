<?php
declare(strict_types=1);
/** @var array $analytics */
$s = $analytics['summary'];
$p = $analytics['progress'];
?>
<section class="admin-dashboard" id="admin-dashboard">
  <div class="dash-kpi-grid">
    <article class="dash-kpi dash-kpi--blue">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">👥</span>
        <span class="dash-kpi__val"><?= (int)$s['total_users'] ?></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_total_users"><?= e_t('admin.dash_total_users') ?></p>
    </article>
    <article class="dash-kpi dash-kpi--teal">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">🧒</span>
        <span class="dash-kpi__val"><?= (int)$s['students'] ?></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_students"><?= e_t('admin.dash_students') ?></p>
    </article>
    <article class="dash-kpi dash-kpi--purple">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">👩‍🏫</span>
        <span class="dash-kpi__val"><?= (int)$s['teachers'] ?></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_teachers"><?= e_t('admin.dash_teachers') ?></p>
    </article>
    <article class="dash-kpi dash-kpi--gold">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">🎓</span>
        <span class="dash-kpi__val"><?= (int)$s['certificates'] ?></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_certificates"><?= e_t('admin.dash_certificates') ?></p>
    </article>
    <article class="dash-kpi dash-kpi--green">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">✅</span>
        <span class="dash-kpi__val"><?= (int)$s['completed_courses'] ?></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_completed_courses"><?= e_t('admin.dash_completed_courses') ?></p>
    </article>
    <article class="dash-kpi dash-kpi--sky">
      <div class="dash-kpi__row">
        <span class="dash-kpi__icon" aria-hidden="true">📅</span>
        <span class="dash-kpi__val dash-kpi__val--split"><?= (int)$s['new_today'] ?> <small>/ <?= (int)$s['new_7d'] ?></small></span>
      </div>
      <p class="dash-kpi__lbl" data-i18n="admin.dash_new_regs"><?= e_t('admin.dash_new_regs') ?></p>
    </article>
  </div>

  <div class="dash-progress-section">
    <h3 class="dash-section-title" data-i18n="admin.dash_progress_title"><?= e_t('admin.dash_progress_title') ?></h3>
    <div class="dash-progress-grid">
      <?php foreach ($p as $item): ?>
      <article class="dash-progress-card" data-admin-progress="<?= e($item['key']) ?>">
        <div class="dash-progress-card__head">
          <span data-i18n="<?= e($item['label_key']) ?>"><?= e_t($item['label_key']) ?></span>
          <strong><?= (int)$item['value'] ?>%</strong>
        </div>
        <div class="dash-progress-bar"><span style="width:<?= (int)$item['value'] ?>%"></span></div>
        <p class="dash-progress-hint">
          <span class="dash-progress-hint__num"><?= e($item['hint_num']) ?></span><?php if (!empty($item['hint_key'])): ?> <span data-i18n="<?= e($item['hint_key']) ?>"><?= e_t($item['hint_key']) ?></span><?php endif; ?>
        </p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dash-charts-grid">
    <article class="dash-chart-card dash-chart-card--wide">
      <h3 class="dash-section-title" data-i18n="admin.dash_chart_growth"><?= e_t('admin.dash_chart_growth') ?></h3>
      <div class="dash-chart-wrap"><canvas id="chart-users-growth" aria-label="<?= e_t('admin.dash_chart_growth') ?>"></canvas></div>
    </article>
    <article class="dash-chart-card">
      <h3 class="dash-section-title" data-i18n="admin.dash_chart_regs"><?= e_t('admin.dash_chart_regs') ?></h3>
      <div class="dash-chart-wrap"><canvas id="chart-registrations" aria-label="<?= e_t('admin.dash_chart_regs') ?>"></canvas></div>
    </article>
    <article class="dash-chart-card">
      <h3 class="dash-section-title" data-i18n="admin.dash_chart_certs"><?= e_t('admin.dash_chart_certs') ?></h3>
      <div class="dash-chart-wrap"><canvas id="chart-certificates" aria-label="<?= e_t('admin.dash_chart_certs') ?>"></canvas></div>
    </article>
    <article class="dash-chart-card">
      <h3 class="dash-section-title" data-i18n="admin.dash_chart_roles"><?= e_t('admin.dash_chart_roles') ?></h3>
      <div class="dash-chart-wrap dash-chart-wrap--pie"><canvas id="chart-roles" aria-label="<?= e_t('admin.dash_chart_roles') ?>"></canvas></div>
    </article>
  </div>
</section>

<script type="application/json" id="admin-charts-data"><?= json_encode(
    ['byLang' => $analytics['charts_i18n'] ?? ['ru' => $analytics['charts']]],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
) ?></script>
<script type="application/json" id="admin-progress-data"><?= json_encode(
    ['byLang' => $analytics['progress_i18n'] ?? ['ru' => $analytics['progress']]],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
) ?></script>
