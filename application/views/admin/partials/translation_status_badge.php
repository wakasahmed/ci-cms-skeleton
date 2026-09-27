<?php
$this->load->helper('manage_translation');
$translation_status = isset($translation_status) ? $translation_status : 'MISSING';
$meta = manage_translation_status_meta($translation_status);
?>
<span class="translation-status-badge <?php echo htmlspecialchars($meta['class'], ENT_QUOTES, 'UTF-8'); ?>" data-translation-badge data-record-id="<?php echo isset($translation_entity_id) ? htmlspecialchars((string) $translation_entity_id, ENT_QUOTES, 'UTF-8') : ''; ?>" data-status="<?php echo htmlspecialchars($translation_status, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
