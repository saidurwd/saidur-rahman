<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label' => null, 'current' => null, 'help' => 'JPG, PNG or WebP.', 'altName' => null, 'altValue' => null]));

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

foreach (array_filter((['name', 'label' => null, 'current' => null, 'help' => 'JPG, PNG or WebP.', 'altName' => null, 'altValue' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php $id = $attributes->get('id', $name); ?>

<div class="mb-3">
    <?php if($label): ?><label for="<?php echo e($id); ?>" class="form-label"><?php echo e($label); ?></label><?php endif; ?>
    <div class="mb-2">
        <img id="<?php echo e($id); ?>-preview" src="<?php echo e($current); ?>" alt="" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['image-upload-preview', 'd-none' => ! $current]); ?>">
    </div>
    <input type="file" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" accept="image/jpeg,image/png,image/webp"
        data-preview="<?php echo e($id); ?>-preview" <?php echo e($attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)])); ?>>
    <?php if($help): ?><div class="form-text"><?php echo e($help); ?></div><?php endif; ?>
    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php if($altName): ?>
        <?php if (isset($component)) { $__componentOriginal375f0ed4f8ee156e823aad8b1382f853 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal375f0ed4f8ee156e823aad8b1382f853 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form.input','data' => ['name' => $altName,'label' => 'Alt text','value' => $altValue,'help' => 'Describe the image for screen readers.','class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($altName),'label' => 'Alt text','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($altValue),'help' => 'Describe the image for screen readers.','class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal375f0ed4f8ee156e823aad8b1382f853)): ?>
<?php $attributes = $__attributesOriginal375f0ed4f8ee156e823aad8b1382f853; ?>
<?php unset($__attributesOriginal375f0ed4f8ee156e823aad8b1382f853); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal375f0ed4f8ee156e823aad8b1382f853)): ?>
<?php $component = $__componentOriginal375f0ed4f8ee156e823aad8b1382f853; ?>
<?php unset($__componentOriginal375f0ed4f8ee156e823aad8b1382f853); ?>
<?php endif; ?>
    <?php endif; ?>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/components/admin/form/image-upload.blade.php ENDPATH**/ ?>