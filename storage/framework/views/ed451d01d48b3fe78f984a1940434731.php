<div wire:poll.20s>
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'Dashboard CSSD','subtitle' => 'Beban kerja dan posisi alat di setiap zona.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Dashboard CSSD','subtitle' => 'Beban kerja dan posisi alat di setiap zona.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('cssd.scan')); ?>" wire:navigate class="btn-primary">Buka Stasiun Scan</a>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

    <?php $citoPending = $pendingIntake->where('is_cito', true); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($citoPending->isNotEmpty()): ?>
        <div class="mb-5 card border-red-300 bg-red-50/80 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-red-900">
                        <?php echo e($citoPending->count()); ?> order CITO menunggu — didahulukan
                    </h2>
                    <p class="mt-0.5 text-sm text-red-800">
                        Segera data dan proses lebih dulu dari antrian biasa.
                    </p>
                </div>
                <a href="<?php echo e(route('cssd.orders', ['status' => \App\Enums\DeliveryOrderStatus::PendingCssdIntake->value])); ?>"
                   wire:navigate class="btn-primary !bg-red-600 hover:!bg-red-700 shrink-0">Proses Sekarang</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $citoPending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('cssd.orders.show', $order->id)); ?>" wire:navigate <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'ci-'.e($order->id).''; ?>wire:key="ci-<?php echo e($order->id); ?>"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-red-300 transition hover:ring-red-500">
                        <span class="font-mono font-medium text-slate-700"><?php echo e($order->order_number); ?></span>
                        <span class="text-slate-500">· <?php echo e($order->originUnit->name); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->needed_at): ?>
                            <span class="font-semibold text-red-600"> · butuh <?php echo e($order->needed_at->format('d/m H:i')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingIntake->isNotEmpty()): ?>
        <div class="mb-5 card border-amber-300 bg-amber-50/70 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">
                        <?php echo e($pendingIntake->count()); ?> order menunggu pendataan
                    </h2>
                    <p class="mt-0.5 text-sm text-amber-800">
                        Unit belum bisa melihat rincian alatnya sampai pendataan disimpan.
                    </p>
                </div>
                <a href="<?php echo e(route('cssd.orders')); ?>" wire:navigate class="btn-primary shrink-0">Lihat Antrian</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $pendingIntake->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('cssd.orders.show', $order->id)); ?>" wire:navigate <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'pi-'.e($order->id).''; ?>wire:key="pi-<?php echo e($order->id); ?>"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-amber-200 transition hover:ring-amber-400">
                        <span class="font-mono font-medium text-slate-700"><?php echo e($order->order_number); ?></span>
                        <span class="text-slate-500">· <?php echo e($order->originUnit->name); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->is_cito): ?>
                            <span class="ml-1 rounded bg-red-100 px-1 py-0.5 text-[10px] font-bold text-red-700">CITO</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $zoneTotals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'zt-'.e($row['zone']->value).''; ?>wire:key="zt-<?php echo e($row['zone']->value); ?>" class="card p-5">
                <div class="text-sm font-medium text-slate-700"><?php echo e($row['zone']->label()); ?></div>
                <div class="mt-1 text-3xl font-semibold text-slate-900"><?php echo e($row['count']); ?></div>
                <div class="mt-1 text-xs text-slate-400"><?php echo e($row['zone']->description()); ?></div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

        <a href="<?php echo e(route('cssd.distribution')); ?>" wire:navigate
           class="card p-5 transition hover:border-teal-300 hover:shadow">
            <div class="text-sm font-medium text-slate-700">Siap Diserahkan</div>
            <div class="mt-1 text-3xl font-semibold text-emerald-700"><?php echo e($readyForDistribution); ?></div>
            <div class="mt-1 text-xs text-slate-400">
                <?php echo e($pendingPickups); ?> serah terima menunggu konfirmasi unit
            </div>
        </a>

        <a href="<?php echo e(route('cssd.distribution')); ?>" wire:navigate
           class="<?php echo \Illuminate\Support\Arr::toCssClasses([
               'card p-5 transition hover:border-red-300 hover:shadow',
               'border-red-300 bg-red-50/40' => $expiredSterileCount > 0,
           ]); ?>">
            <div class="text-sm font-medium text-slate-700">Kedaluwarsa Steril</div>
            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'mt-1 text-3xl font-semibold',
                'text-red-700' => $expiredSterileCount > 0,
                'text-slate-300' => $expiredSterileCount === 0,
            ]); ?>"><?php echo e($expiredSterileCount); ?></div>
            <div class="mt-1 text-xs text-slate-400">
                Alat di gudang, masa sterilnya sudah lewat — perlu sterilisasi ulang
            </div>
        </a>
    </div>

    <div class="mt-5 card">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Rincian per Tahap</h2>
            <p class="mt-0.5 text-xs text-slate-500">Menunjukkan di tahap mana antrian sedang menumpuk.</p>
        </div>

        <div class="grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stageBreakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'sb-'.e($row['status']->value).''; ?>wire:key="sb-<?php echo e($row['status']->value); ?>" class="bg-white p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm text-slate-600"><?php echo e($row['status']->label()); ?></div>
                            <div class="mt-0.5 text-xs text-slate-400"><?php echo e($row['status']->zone()->label()); ?></div>
                        </div>
                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'shrink-0 text-2xl font-semibold',
                            'text-slate-900' => $row['count'] > 0,
                            'text-slate-300' => $row['count'] === 0,
                        ]); ?>"><?php echo e($row['count']); ?></div>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/livewire/cssd/dashboard.blade.php ENDPATH**/ ?>