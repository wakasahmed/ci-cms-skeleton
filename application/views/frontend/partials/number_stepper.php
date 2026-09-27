<?php
// Configure $numberStepper before including this reusable field.
$stepperId = (string) $numberStepper['id'];
$stepperMaximum = max(0, (int) $numberStepper['max']);
?>
<div class="number-stepper" data-number-stepper dir="ltr">
    <button type="button" data-number-step="-1" aria-label="<?php echo e(t('numberStepper.decrease')); ?>" aria-controls="<?php echo e($stepperId); ?>">
        <?php echo icon('fa-solid fa-minus', 'icon-xs'); ?>
    </button>
    <input
        type="number"
        readonly
        id="<?php echo e($stepperId); ?>"
        name="<?php echo e($numberStepper['name']); ?>"
        min="1"
        max="<?php echo $stepperMaximum; ?>"
        step="1"
        value="<?php echo max(1, (int) $numberStepper['value']); ?>"
        <?php echo !empty($numberStepper['required']) ? 'required' : ''; ?>
    >
    <button type="button" data-number-step="1" aria-label="<?php echo e(t('numberStepper.increase')); ?>" aria-controls="<?php echo e($stepperId); ?>">
        <?php echo icon('plus', 'icon-xs'); ?>
    </button>
</div>
