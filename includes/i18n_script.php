<?php
declare(strict_types=1);
?>
<script>
window.FINKID_I18N = <?= json_encode([
    'lang'       => currentLang(),
    'packs'      => getAllTranslationPacks(),
    'courseMeta' => getCourseMetaForI18n(),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
