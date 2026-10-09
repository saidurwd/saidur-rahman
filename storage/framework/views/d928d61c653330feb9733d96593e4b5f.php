<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/site.css', 'resources/js/site.js']); ?>
</head>
<body class="bg-stone-50 text-slate-900">
    <?php echo $__env->yieldContent('content'); ?>
</body>
</html>
<?php /**PATH /Users/saidur.rahman/Code/saidur-rahman/resources/views/site/layouts/app.blade.php ENDPATH**/ ?>