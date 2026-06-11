<?php
declare(strict_types=1);
/** @var array $extraJs */
$extraJs = $extraJs ?? [];
?>
<div id="modal-backdrop" class="mo-backdrop" onclick="closeAllModals()"></div>
<?php require __DIR__ . '/support_chat.php'; ?>
<div id="toast"></div>
<script src="<?= e(assetv('js/i18n.js')) ?>"></script>
<script src="<?= e(assetv('js/app.js')) ?>"></script>
<script src="<?= e(assetv('js/forms.js')) ?>"></script>
<script src="<?= e(assetv('js/fini-widget.js')) ?>"></script>
<?php foreach ($extraJs as $js): ?>
<script src="<?= e(assetv($js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
