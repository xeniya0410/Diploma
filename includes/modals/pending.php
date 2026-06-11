<div class="mo" id="modal-pending" role="dialog">
  <div class="md md-auth md-auth--mockup">
    <div class="pending-box">
      <div class="pb-ico">📨</div>
      <h3 style="font-size:.95rem;font-weight:900;color:#92400E;margin-bottom:.3rem;" data-i18n="auth.pending_title"><?= e_t('auth.pending_title') ?></h3>
      <p style="font-size:.8rem;color:#B45309;font-weight:600;" data-i18n="auth.pending_desc"><?= e_t('auth.pending_desc') ?></p>
    </div>
    <div style="margin-top:1rem;">
      <button type="button" class="btn-ghost-ui" onclick="closeModal('pending')" data-i18n="auth.pending_ok"><?= e_t('auth.pending_ok') ?></button>
    </div>
  </div>
</div>
