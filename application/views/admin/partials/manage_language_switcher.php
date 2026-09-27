<?php
$escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$showTranslationStatus = !empty($translation_module) && !empty($translation_entity_id) && isset($translation_status);
$showTranslationControl = !empty($translation_module)
    && !empty($translation_entity_id)
    && $locale === 'ar'
    && (!isset($can_update) || !empty($can_update));
?>
<div class="manage-language-tools">
    <?php if ($showTranslationStatus) { ?>
        <div class="manage-translation-state">
            <span class="manage-translation-state-label">Arabic</span>
            <?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => $translation_status, 'translation_entity_id' => $translation_entity_id)); ?>
        </div>
    <?php } ?>
    <?php if ($showTranslationControl) { ?>
        <button type="button" class="btn btn-outline-primary" data-translate-source data-module="<?php echo $escape($translation_module); ?>" data-record-id="<?php echo $escape($translation_entity_id); ?>" data-url="<?php echo $escape(base_url('manage/translations/translate')); ?>">
            <i class="bi bi-translate me-1" aria-hidden="true"></i><span data-translate-label><?php echo !empty($translation_ready) ? 'Translate again' : 'Translate from English'; ?></span>
        </button>
    <?php } ?>
    <div class="content-language-switch" role="group" aria-label="Content language">
    <?php foreach ($locales as $localeKey => $localeDetails) {
        $targetUrl = $language_base_url.'?lang='.rawurlencode($localeKey); ?>
        <a href="<?php echo $escape($targetUrl); ?>" data-language-switch data-locale="<?php echo $escape($localeKey); ?>" class="btn manage-language-tab<?php echo $locale === $localeKey ? ' is-active' : ''; ?>"<?php echo $locale === $localeKey ? ' aria-current="true"' : ''; ?>><?php echo $escape($localeDetails['label']); ?></a>
    <?php } ?>
    </div>
    <?php if ($showTranslationControl) { ?><span class="manage-translation-feedback visually-hidden" data-translation-feedback aria-live="polite"></span><?php } ?>
</div>
