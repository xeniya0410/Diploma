<?php
declare(strict_types=1);
$authError = flash('auth_error');
$returnUrl = 'index.php';
$uri = strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '#') ?: '';
if ($uri !== '') {
    $base = computeAppBase();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = ltrim($uri, '/');
    if ($uri !== '' && !str_contains($uri, '//')) {
        $returnUrl = $uri;
    }
}
?>
<div class="mo" id="modal-login" role="dialog">
  <div class="md md-auth md-auth--mockup md-auth--login">
    <button type="button" class="mo-close" onclick="closeModal('login')" aria-label="<?= e_t('nav.close') ?>">✕</button>
    <div class="md-logo">🦊 <span data-i18n="site.name"><?= e_t('site.name') ?></span></div>
    <h2 class="md-title" data-i18n="auth.welcome"><?= e_t('auth.welcome') ?></h2>
    <p class="md-sub" data-i18n="auth.sub_login"><?= e_t('auth.sub_login') ?></p>
    <?php if ($authError && ($_GET['auth'] ?? '') === 'login'): ?><p class="auth-error"><?= e($authError) ?></p><?php endif; ?>
    <form class="auth-form" method="post" action="<?= e(asset('login.php')) ?>" data-ajax="1">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="lang" class="auth-lang-field" value="<?= e(currentLang()) ?>">
      <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
      <div class="fg"><label class="fl" data-i18n="auth.email"><?= e_t('auth.email') ?></label>
        <input class="fi" type="email" name="email" required autocomplete="email" data-i18n-placeholder="auth.email_placeholder" placeholder="<?= e_t('auth.email_placeholder') ?>"></div>
      <div class="fg"><label class="fl" data-i18n="auth.password"><?= e_t('auth.password') ?></label>
        <input class="fi" type="password" name="password" required autocomplete="current-password"></div>
      <button type="submit" class="btn-primary-ui" data-i18n="auth.login_btn"><?= e_t('auth.login_btn') ?></button>
    </form>
    <p class="md-sw"><span data-i18n="auth.no_account"><?= e_t('auth.no_account') ?></span>
      <button type="button" class="auth-link" onclick="switchModal('register')" data-i18n="nav.register"><?= e_t('nav.register') ?></button></p>
  </div>
</div>

<div class="mo" id="modal-register" role="dialog">
  <div class="md md-auth md-auth--mockup md-auth--register">
    <button type="button" class="mo-close" onclick="closeModal('register')" aria-label="<?= e_t('nav.close') ?>">✕</button>
    <div class="md-logo">🦊 <span data-i18n="site.name"><?= e_t('site.name') ?></span></div>
    <h2 class="md-title" data-i18n="auth.register_title"><?= e_t('auth.register_title') ?></h2>
    <p class="md-sub" data-i18n="auth.sub_register"><?= e_t('auth.sub_register') ?></p>
    <?php if ($authError && ($_GET['auth'] ?? '') === 'register'): ?><p class="auth-error"><?= e($authError) ?></p><?php endif; ?>

    <div class="rtabs" id="auth-role-cards">
      <div class="rtab on" data-role="student" role="button" tabindex="0">
        <div class="rico">🧒</div>
        <div class="rn" data-i18n="auth.role_student"><?= e_t('auth.role_student') ?></div>
        <div class="rd" data-i18n="auth.role_student_desc"><?= e_t('auth.role_student_desc') ?></div>
      </div>
      <div class="rtab" data-role="teacher" role="button" tabindex="0">
        <div class="rico">👩‍🏫</div>
        <div class="rn" data-i18n="auth.role_teacher"><?= e_t('auth.role_teacher') ?></div>
        <div class="rd" data-i18n="auth.role_teacher_desc"><?= e_t('auth.role_teacher_desc') ?></div>
      </div>
      <div class="rtab" data-role="admin" role="button" tabindex="0">
        <div class="rico">⚙️</div>
        <div class="rn" data-i18n="auth.role_admin"><?= e_t('auth.role_admin') ?></div>
        <div class="rd" data-i18n="auth.role_admin_desc"><?= e_t('auth.role_admin_desc') ?></div>
      </div>
    </div>

    <div class="cert-note" id="cert-note-student" data-i18n="auth.banner"><?= e_t('auth.banner') ?></div>

    <form class="auth-form" method="post" action="<?= e(asset('register.php')) ?>" data-ajax="1" id="register-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="lang" class="auth-lang-field" value="<?= e(currentLang()) ?>">
      <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
      <input type="hidden" name="role" id="register-role" value="student">

      <div class="form-row">
        <div class="fg"><label class="fl" data-i18n="auth.name"><?= e_t('auth.name') ?></label>
          <input class="fi" type="text" name="name" required maxlength="120" data-i18n-placeholder="auth.name_placeholder" placeholder="<?= e_t('auth.name_placeholder') ?>"></div>
        <div class="fg" id="age-group"><label class="fl" data-i18n="auth.age"><?= e_t('auth.age') ?></label>
          <select class="fi" name="age_band" id="ra">
            <option value="9">8–10 <?= e_t('auth.years') ?></option>
            <option value="12" selected>11–12 <?= e_t('auth.years') ?></option>
            <option value="14">13–14 <?= e_t('auth.years') ?></option>
          </select>
          <input type="hidden" name="age" id="age-hidden" value="12"></div>
      </div>

      <div class="fg"><label class="fl" data-i18n="auth.email"><?= e_t('auth.email') ?> *</label>
        <input class="fi" type="email" name="email" required data-i18n-placeholder="auth.email_placeholder" placeholder="<?= e_t('auth.email_placeholder') ?>">
        <p class="fn" id="email-hint-student" data-i18n="auth.email_hint"><?= e_t('auth.email_hint') ?></p>
      </div>

      <div class="fg"><label class="fl" data-i18n="auth.password"><?= e_t('auth.password') ?></label>
        <input class="fi" type="password" name="password" required minlength="8" placeholder="<?= e_t('auth.password_min') ?>"></div>

      <div class="teacher-x" id="teacher-x">
        <div class="fg"><label class="fl" data-i18n="auth.org"><?= e_t('auth.org') ?></label>
          <input class="fi" type="text" name="organization" placeholder="<?= e_t('auth.org_placeholder') ?>"></div>
        <div class="fg"><label class="fl" data-i18n="auth.exp"><?= e_t('auth.exp') ?></label>
          <input class="fi" type="text" name="experience" placeholder="<?= e_t('auth.exp_placeholder') ?>"></div>
        <p class="fn" data-i18n="auth.teacher_note"><?= e_t('auth.teacher_note') ?></p>
      </div>

      <div class="admin-x" id="admin-x">
        <div class="fg"><label class="fl" data-i18n="auth.admin_code"><?= e_t('auth.admin_code') ?></label>
          <input class="fi" type="password" name="admin_code" id="acode" placeholder="<?= e_t('auth.admin_code_ph') ?>"></div>
        <p class="fn" data-i18n="auth.admin_note"><?= e_t('auth.admin_note') ?></p>
      </div>

      <button type="submit" class="btn-primary-ui" data-i18n="auth.register_btn"><?= e_t('auth.register_btn') ?></button>
    </form>
    <p class="md-sw"><span data-i18n="auth.has_account"><?= e_t('auth.has_account') ?></span>
      <button type="button" class="auth-link" onclick="switchModal('login')" data-i18n="nav.login"><?= e_t('nav.login') ?></button></p>
  </div>
</div>

<?php require __DIR__ . '/pending.php'; ?>
