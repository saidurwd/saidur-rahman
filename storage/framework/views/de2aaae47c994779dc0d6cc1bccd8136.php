<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null, 'footer' => null, 'flush' => false]));

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

foreach (array_filter((['title' => null, 'footer' => null, 'flush' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->class(['card', 'mb-4'])); ?>>
    <?php if($title || isset($actions)): ?>
        <div class="card-header">
            <h3 class="card-title"><?php echo e($title); ?></h3>
            <?php if(isset($actions)): ?>
                <div class="card-tools"><?php echo e($actions); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['card-body', 'p-0' => $flush]); ?>"><?php echo e($slot); ?></div>
    <?php if($footer): ?>
        <div class="card-footer"><?php echo e($footer); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/card.blade.php ENDPATH**/ ?>