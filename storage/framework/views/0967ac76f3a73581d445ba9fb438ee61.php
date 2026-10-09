<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'icon' => 'bar-chart', 'href' => null, 'tone' => 'primary']));

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

foreach (array_filter((['label', 'value', 'icon' => 'bar-chart', 'href' => null, 'tone' => 'primary']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="info-box">
    <span class="info-box-icon text-bg-<?php echo e($tone); ?> shadow-sm"><i class="bi bi-<?php echo e($icon); ?>"></i></span>
    <div class="info-box-content">
        <span class="info-box-text">
            <?php if($href): ?><a href="<?php echo e($href); ?>" class="text-reset text-decoration-none"><?php echo e($label); ?></a><?php else: ?><?php echo e($label); ?><?php endif; ?>
        </span>
        <span class="info-box-number"><?php echo e($value); ?></span>
    </div>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/stat.blade.php ENDPATH**/ ?>