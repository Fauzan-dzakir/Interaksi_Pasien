<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'SIM Alat CSSD'); ?> — RS Kemenkes Surabaya</title>
    <link rel="icon" href="<?php echo e(asset('favicon.ico')); ?>" sizes="any">
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/favicon-192.png')); ?>" sizes="192x192">
    <link rel="apple-touch-icon" href="<?php echo e(asset('images/apple-touch-icon.png')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="h-full bg-linear-to-br from-slate-50 via-slate-100 to-teal-50/50 text-slate-800 antialiased">

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
    <?php if (isset($component)) { $__componentOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast-stack','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast-stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007)): ?>
<?php $attributes = $__attributesOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007; ?>
<?php unset($__attributesOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007)): ?>
<?php $component = $__componentOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007; ?>
<?php unset($__componentOriginal8cfcb0fc4ab55bb76cadc5ba7a0de007); ?>
<?php endif; ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="min-h-full flex flex-col md:flex-row">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
        <?php echo $__env->make('partials.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-8 print:max-w-none print:p-0">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php echo e($slot); ?>

    </main>
</div>

<!-- TomSelect JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/layouts/app.blade.php ENDPATH**/ ?>