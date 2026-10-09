<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['headers' => [], 'empty' => 'Nothing here yet.']));

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

foreach (array_filter((['headers' => [], 'empty' => 'Nothing here yet.']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="table-responsive">
    <table <?php echo e($attributes->class(['table table-hover align-middle mb-0'])); ?>>
        <?php if($headers): ?>
            <thead>
                <tr>
                    <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th scope="col"><?php echo e($header); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </thead>
        <?php endif; ?>
        <tbody>
            <?php if($slot->isEmpty()): ?>
                <tr><td colspan="<?php echo e(max(count($headers), 1)); ?>" class="text-center text-body-secondary py-4"><?php echo e($empty); ?></td></tr>
            <?php else: ?>
                <?php echo e($slot); ?>

            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/table.blade.php ENDPATH**/ ?>