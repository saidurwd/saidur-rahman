<?php $__env->startSection('title', 'Two-factor check'); ?>

<?php $__env->startSection('content'); ?>
    <h2 class="h5 mb-1">Two-factor check</h2>
    <p class="text-body-secondary small">Enter the 6-digit code from your authenticator app.</p>

    <form method="POST" action="<?php echo e(route('admin.two-factor.challenge.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if (isset($component)) { $__componentOriginal375f0ed4f8ee156e823aad8b1382f853 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal375f0ed4f8ee156e823aad8b1382f853 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form.input','data' => ['name' => 'code','label' => 'Authentication code','inputmode' => 'numeric','autocomplete' => 'one-time-code','maxlength' => '7','class' => 'otp-input','autofocus' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'code','label' => 'Authentication code','inputmode' => 'numeric','autocomplete' => 'one-time-code','maxlength' => '7','class' => 'otp-input','autofocus' => true]); ?>
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
        <button type="submit" class="btn btn-primary w-100">Verify</button>
    </form>

    <details class="mt-4" <?php if($errors->has('recovery_code')): ?> open <?php endif; ?>>
        <summary class="small text-body-secondary">Use a recovery code instead</summary>
        <form method="POST" action="<?php echo e(route('admin.two-factor.challenge.store')); ?>" class="mt-3">
            <?php echo csrf_field(); ?>
            <?php if (isset($component)) { $__componentOriginal375f0ed4f8ee156e823aad8b1382f853 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal375f0ed4f8ee156e823aad8b1382f853 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form.input','data' => ['name' => 'recovery_code','label' => 'Recovery code','autocomplete' => 'off','placeholder' => 'xxxxx-xxxxx']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'recovery_code','label' => 'Recovery code','autocomplete' => 'off','placeholder' => 'xxxxx-xxxxx']); ?>
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
            <button type="submit" class="btn btn-outline-primary w-100">Use recovery code</button>
        </form>
    </details>

    <form method="POST" action="<?php echo e(route('admin.logout')); ?>" class="mt-4 text-center">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-link btn-sm text-body-secondary">Cancel and log out</button>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/admin/auth/two-factor-challenge.blade.php ENDPATH**/ ?>