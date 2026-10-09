<?php $__env->startSection('content'); ?>
    <main class="mx-auto max-w-3xl p-8">
        <h1 class="text-3xl font-semibold"><?php echo e(config('app.name')); ?></h1>
        <p class="mt-2">Site placeholder.</p>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('site.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/site/pages/home.blade.php ENDPATH**/ ?>