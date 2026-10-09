<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label', 'checked' => false, 'help' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['name', 'label', 'checked' => false, 'help' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php $id = $attributes->get('id', $name); ?>

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="<?php echo e($name); ?>" value="0">
        <input type="checkbox" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" value="1" role="switch"
            <?php if((bool) old($name, $checked)): echo 'checked'; endif; ?> <?php echo e($attributes->except('id')->class(['form-check-input'])); ?>>
        <label class="form-check-label" for="<?php echo e($id); ?>"><?php echo e($label); ?></label>
    </div>
    <?php if($help): ?><div class="form-text"><?php echo e($help); ?></div><?php endif; ?>
    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/form/toggle.blade.php ENDPATH**/ ?>