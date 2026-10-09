<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="brand-link d-flex align-items-center gap-2">
            <?php if (isset($component)) { $__componentOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f6e91b36d25dd5bc57a8cdf1b87c8c0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.monogram','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.monogram'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
            <span class="brand-text"><?php echo e(config('app.name')); ?></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Admin">
            <ul class="nav sidebar-menu flex-column" role="menu">
                <?php $__currentLoopData = config('admin.menu'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($item['header'])): ?>
                        <li class="nav-header"><?php echo e(strtoupper($item['header'])); ?></li>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php
                        $exists = Route::has($item['route']);
                        $active = $exists && request()->routeIs($item['route']);
                        $badge = isset($item['badge']) ? ($sidebarBadges[$item['badge']] ?? 0) : 0;
                    ?>
                    <li class="nav-item">
                        <?php if($exists && ($item['method'] ?? 'get') === 'post'): ?>
                            <form method="POST" action="<?php echo e(route($item['route'])); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="nav-link w-100 text-start bg-transparent border-0">
                                    <i class="nav-icon bi bi-<?php echo e($item['icon']); ?>"></i><p><?php echo e($item['label']); ?></p>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e($exists ? route($item['route']) : '#'); ?>"
                               class="<?php echo \Illuminate\Support\Arr::toCssClasses(['nav-link', 'active' => $active, 'disabled' => ! $exists]); ?>"
                               <?php if(! $exists): ?> aria-disabled="true" tabindex="-1" title="Coming in a later phase" <?php endif; ?>
                               <?php if($active): ?> aria-current="page" <?php endif; ?>>
                                <i class="nav-icon bi bi-<?php echo e($item['icon']); ?>"></i>
                                <p>
                                    <?php echo e($item['label']); ?>

                                    <?php if($badge > 0): ?><span class="nav-badge badge text-bg-primary me-3"><?php echo e($badge); ?></span><?php endif; ?>
                                </p>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </nav>
    </div>
</aside>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/admin/partials/sidebar.blade.php ENDPATH**/ ?>