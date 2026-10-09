<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" data-bs-theme="dark">
<head>
    <?php echo $__env->make('admin.partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="bg-body-tertiary">
<div class="login-page">
    <div class="login-box" style="width: min(26rem, 92vw)">
        <div class="text-center mb-4">
            <?php if (isset($component)) { $__componentOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.monogram','data' => ['style' => 'width:3.5rem;height:3.5rem']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.monogram'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['style' => 'width:3.5rem;height:3.5rem']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0)): ?>
<?php $attributes = $__attributesOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0; ?>
<?php unset($__attributesOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0)): ?>
<?php $component = $__componentOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0; ?>
<?php unset($__componentOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0); ?>
<?php endif; ?>
            <h1 class="h4 mt-3 mb-0"><?php echo e(config('app.name')); ?></h1>
            <p class="text-body-secondary small mb-0">Admin</p>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-body login-card-body p-4">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $__env->make('admin.partials.toasts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/admin/layouts/auth.blade.php ENDPATH**/ ?>