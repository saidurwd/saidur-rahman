<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status']));

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

foreach (array_filter((['status']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = match ($value) {
        'published', 'active', 'approved', 'replied' => 'success',
        'scheduled' => 'info',
        'new' => 'primary',
        'archived' => 'warning',
        'spam' => 'danger',
        default => 'secondary',
    };
?>

<span <?php echo e($attributes->class(["badge text-bg-{$tone}"])); ?>><?php echo e(\Illuminate\Support\Str::headline($value)); ?></span>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/status-badge.blade.php ENDPATH**/ ?>