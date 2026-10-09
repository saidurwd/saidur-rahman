<?php
    $toasts = collect([
        ['status', 'success', 'check-circle-fill'],
        ['warning', 'warning', 'exclamation-triangle-fill'],
        ['error', 'danger', 'x-octagon-fill'],
    ])->filter(fn ($t) => session()->has($t[0]));
?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090">
    <?php $__currentLoopData = $toasts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="toast align-items-center text-bg-<?php echo e($tone); ?> border-0" role="status" aria-live="polite" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-<?php echo e($icon); ?> me-2"></i><?php echo e(session($key)); ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/admin/partials/toasts.blade.php ENDPATH**/ ?>