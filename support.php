<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $userId  = isAuth() ? (int)$_SESSION['user_id'] : null;
    $fullMsg = ($subject !== '' ? "[$subject] " : '') . $message;

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
        $error = __('support.fill_all');
    } else {
        $pdo->prepare(
            'INSERT INTO support_messages (user_id, name, email, message, status, created_at) VALUES (?,?,?,?,"new",NOW())'
        )->execute([$userId, $name, $email, $fullMsg]);
        $sent = true;
    }
}

$user = isAuth() ? currentUser() : null;
$pageTitle = __('support.title') . ' — ' . __('site.name');
$extraCss  = ['css/pages/panels.css'];
require __DIR__ . '/includes/header.php';
?>

<div class="container panel-page supbody">
  <h1 class="page-title">💬 <?= e_t('support.title') ?></h1>

  <div class="sup-card">
    <h3>💬 <span data-i18n="sup.chat_title"><?= e_t('sup.chat_title') ?></span></h3>
    <p data-i18n="sup.chat_desc"><?= e_t('sup.chat_desc') ?></p>
    <button type="button" class="btn-primary" onclick="toggleChat()" data-i18n="sup.chat_btn"><?= e_t('sup.chat_btn') ?></button>
  </div>

  <div class="sup-card">
    <h3>💚 <span data-i18n="sup.wa_title"><?= e_t('sup.wa_title') ?></span></h3>
    <p data-i18n="sup.wa_desc"><?= e_t('sup.wa_desc') ?></p>
    <?php $waSupportUrl = WHATSAPP_URL . '?text=' . urlencode(__('faq.wa_prefill')); ?>
    <a class="wa-link" href="<?= e($waSupportUrl) ?>" target="_blank" rel="noopener" data-wa-prefill-key="faq.wa_prefill">
      💬 <span data-i18n="sup.wa_btn"><?= e_t('sup.wa_btn') ?></span>
    </a>
  </div>

  <div class="sup-card">
    <h3>📧 <span data-i18n="sup.em_title"><?= e_t('sup.em_title') ?></span></h3>
    <p data-i18n="sup.em_desc"><?= e_t('sup.em_desc') ?></p>
    <?php if ($sent): ?>
      <div class="form-sent show">
        <div class="fs-ico">✅</div>
        <h4 data-i18n="sup.em_sent_title"><?= e_t('sup.em_sent_title') ?></h4>
        <p data-i18n="sup.em_sent_desc"><?= e_t('sup.em_sent_desc') ?></p>
      </div>
    <?php else: ?>
      <?php if ($error): ?><p class="flash flash-err"><?= e($error) ?></p><?php endif; ?>
      <form method="post" id="email-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="fg"><label class="fl" data-i18n="auth.name"><?= e_t('auth.name') ?></label>
          <input class="fi" type="text" name="name" required value="<?= e($user['name'] ?? '') ?>"></div>
        <div class="fg"><label class="fl" data-i18n="auth.email"><?= e_t('auth.email') ?></label>
          <input class="fi" type="email" name="email" required value="<?= e($user['email'] ?? '') ?>"></div>
        <div class="fg"><label class="fl" data-i18n="sup.subject"><?= e_t('sup.subject') ?></label>
          <select class="fi" name="subject">
            <option value="<?= e(__('sup.subj_course')) ?>"><?= e_t('sup.subj_course') ?></option>
            <option value="<?= e(__('sup.subj_tech')) ?>"><?= e_t('sup.subj_tech') ?></option>
            <option value="<?= e(__('sup.subj_account')) ?>"><?= e_t('sup.subj_account') ?></option>
            <option value="<?= e(__('sup.subj_idea')) ?>"><?= e_t('sup.subj_idea') ?></option>
            <option value="<?= e(__('sup.subj_other')) ?>"><?= e_t('sup.subj_other') ?></option>
          </select></div>
        <div class="fg"><label class="fl" data-i18n="support.message"><?= e_t('support.message') ?></label>
          <textarea class="fta fi" name="message" rows="4" required></textarea></div>
        <button type="submit" class="btn-primary" data-i18n="support.send"><?= e_t('support.send') ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
