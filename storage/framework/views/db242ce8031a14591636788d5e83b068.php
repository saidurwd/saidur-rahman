<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['action', 'confirm' => 'Delete this item? You can restore it from Trash.', 'label' => 'Delete', 'size' => 'sm']));

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

foreach (array_filter((['action', 'confirm' => 'Delete this item? You can restore it from Trash.', 'label' => 'Delete', 'size' => 'sm']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<form method="POST" action="<?php echo e($action); ?>" data-confirm="<?php echo e($confirm); ?>" class="d-inline">
    <?php echo csrf_field(); ?>
    <?php echo method_field('DELETE'); ?>
    <button type="submit" <?php echo e($attributes->class(["btn btn-{$size} btn-outline-danger"])); ?>>
        <i class="bi bi-trash" aria-hidden="true"></i> <?php echo e($label); ?>

    </button>
</form>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/delete-button.blade.php ENDPATH**/ ?>