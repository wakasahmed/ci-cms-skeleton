<?php defined('BASEPATH') OR exit('No direct script access allowed');

$ratingName = isset($name) ? (string) $name : 'rating';
$ratingKey = preg_replace('/[^a-z0-9_-]+/i', '-', $ratingName);
$ratingMax = isset($max) ? (int) $max : 5;
if ($ratingMax < 1) $ratingMax = 5;
$ratingValue = isset($value) ? (int) $value : 0;
if ($ratingValue < 0) $ratingValue = 0;
if ($ratingValue > $ratingMax) $ratingValue = $ratingMax;
$ratingLabel = isset($label) ? (string) $label : 'Rating';
$ratingRequired = !empty($required);
$ratingHint = isset($hint) ? (string) $hint : '';
$ratingEmptyText = isset($empty_text) ? (string) $empty_text : 'No rating selected';
?>
<div class="admin-field mb-3 admin-star-field" data-star-rating data-star-max="<?php echo $ratingMax; ?>" data-star-empty-text="<?php echo htmlspecialchars($ratingEmptyText, ENT_QUOTES, 'UTF-8'); ?>">
    <label class="form-label<?php echo $ratingRequired ? ' is-required' : ''; ?>" id="<?php echo $ratingKey; ?>-label"><?php echo htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?></label>
    <input type="hidden" name="<?php echo htmlspecialchars($ratingName, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo $ratingKey; ?>" value="<?php echo $ratingValue; ?>" data-star-input>
    <div class="admin-star-row">
        <div class="admin-stars" role="radiogroup" aria-labelledby="<?php echo $ratingKey; ?>-label" data-star-group>
            <?php for ($star = 1; $star <= $ratingMax; $star++) { $selected = ($star === $ratingValue); ?>
            <button type="button" class="admin-star<?php echo ($star <= $ratingValue) ? ' is-on' : ''; ?>" role="radio" aria-checked="<?php echo $selected ? 'true' : 'false'; ?>" aria-label="<?php echo $star; ?> star<?php echo $star > 1 ? 's' : ''; ?>" tabindex="<?php echo ($selected || ($ratingValue === 0 && $star === 1)) ? '0' : '-1'; ?>" data-star-value="<?php echo $star; ?>">
                <i class="bi <?php echo ($star <= $ratingValue) ? 'bi-star-fill' : 'bi-star'; ?>" aria-hidden="true"></i>
            </button>
            <?php } ?>
        </div>
        <span class="admin-star-caption hidden" data-star-caption aria-live="polite"><?php echo $ratingValue > 0 ? $ratingValue.' of '.$ratingMax : htmlspecialchars($ratingEmptyText, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php if ($ratingHint !== '') { ?><div class="form-text"><?php echo htmlspecialchars($ratingHint, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
</div>
