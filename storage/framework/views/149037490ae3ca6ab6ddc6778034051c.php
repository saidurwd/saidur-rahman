<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $__env->yieldContent('title', 'Admin'); ?> · <?php echo e(config('app.name')); ?></title>
<link rel="icon" href="<?php echo e(asset('favicon.svg')); ?>" type="image/svg+xml">
<?php echo app('Illuminate\Foundation\Vite')(['resources/css/admin.css', 'resources/js/admin.js']); ?>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/admin/partials/head.blade.php ENDPATH**/ ?>