<?php declare(strict_types=1); ?>
<!-- finkid-chat-v2 -->
<button type="button" id="spbtn" aria-label="<?= e_t('ai.title') ?>" aria-expanded="false" aria-controls="sppanel">
  💬
  <span class="nd"></span>
</button>
<div id="sppanel" role="dialog" aria-labelledby="sp-name" aria-hidden="true"
  data-support-url="<?= e(asset('support.php')) ?>">
  <div class="sphdr">
    <div class="spha">🦊</div>
    <div class="sphi">
      <h4 id="sp-name" data-i18n="ai.title"><?= e_t('ai.title') ?></h4>
      <div class="spon"><div class="spod"></div><p id="sp-status" data-i18n="ai.online"><?= e_t('ai.online') ?></p></div>
    </div>
    <button type="button" class="spclose" aria-label="<?= e_t('nav.close') ?>">✕</button>
  </div>
  <div class="chatmsgs" id="chatmsgs"></div>
  <div class="qbtns" id="chat-qbtns">
    <button type="button" class="qb" data-topic="reg" data-i18n="ai.q_reg"><?= e_t('ai.q_reg') ?></button>
    <button type="button" class="qb" data-topic="cert" data-i18n="ai.q_cert"><?= e_t('ai.q_cert') ?></button>
    <button type="button" class="qb" data-topic="support" data-i18n="ai.q_support"><?= e_t('ai.q_support') ?></button>
    <button type="button" class="qb" data-topic="money" data-i18n="ai.q_money"><?= e_t('ai.q_money') ?></button>
    <button type="button" class="qb" data-topic="budget" data-i18n="ai.q_budget"><?= e_t('ai.q_budget') ?></button>
    <button type="button" class="qb" data-topic="savings" data-i18n="ai.q_savings"><?= e_t('ai.q_savings') ?></button>
    <button type="button" class="qb" data-topic="percent" data-i18n="ai.q_percent"><?= e_t('ai.q_percent') ?></button>
  </div>
  <div class="chatrow">
    <input class="chatinp" id="chatinp" data-i18n-placeholder="ai.placeholder" placeholder="<?= e_t('ai.placeholder') ?>" autocomplete="off">
    <button type="button" class="chatsend" aria-label="<?= e_t('ai.send') ?>">➤</button>
  </div>
</div>
