<div wire:poll.15s x-data="{ tab: 'table' }">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'Pendataan Alat di Unit','subtitle' => 'Informasi alat real-time di unit Anda — tandai alat yang sudah dipakai.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pendataan Alat di Unit','subtitle' => 'Informasi alat real-time di unit Anda — tandai alat yang sudah dipakai.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($feedback): ?>
        <p class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'mb-4 rounded-lg border px-4 py-3 text-sm',
            'border-emerald-300 bg-emerald-50 text-emerald-700' => $feedbackType === 'success',
            'border-red-300 bg-red-50 text-red-600' => $feedbackType === 'error',
            'border-slate-300 bg-slate-50 text-slate-500' => $feedbackType === 'info',
        ]); ?>"><?php echo e($feedback); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="mb-4 flex gap-2 lg:hidden">
        <button type="button" @click="tab = 'table'"
                :class="tab === 'table' ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">Daftar Alat</button>
        <button type="button" @click="tab = 'scan'"
                :class="tab === 'scan' ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">Scan Alat</button>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2" :class="tab === 'table' ? 'block' : 'hidden lg:block'">
            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Alat Belum Dipakai (<?php echo e($batches->count()); ?>)</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Alat hasil pengambilan dari CSSD yang masih ada di unit ini. Begitu ditandai
                        dipakai, alat pindah ke daftar "Alat di Unit Ini" pada halaman Buat Order.
                    </p>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batches->isEmpty()): ?>
                    <div class="px-5 py-10 text-center text-sm text-slate-400">
                        Belum ada alat yang diambil dari CSSD — kalau ini pengiriman pertama, itu wajar.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'inv-'.e($batch->id).''; ?>wire:key="inv-<?php echo e($batch->id); ?>" class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="font-mono text-xs text-slate-500"><?php echo e($batch->public_code); ?></span>
                                        <div class="font-medium text-slate-900"><?php echo e($batch->displayName()); ?></div>
                                    </div>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batch->batch_type === \App\Enums\BatchType::Individual): ?>
                                        <button wire:click="toggleIndividualUsage(<?php echo e($batch->id); ?>)"
                                                class="btn-primary !px-3 !py-1.5">Tandai Dipakai</button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batch->batch_type === \App\Enums\BatchType::Set && $batch->instrumentSet): ?>
                                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                        <p class="mb-2 text-xs font-semibold text-slate-500">Tandai per alat dalam set ini</p>
                                        <div class="space-y-1.5">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $batch->instrumentSet->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <?php
                                                    $mark = $batch->usageMarks->firstWhere('item_id', $setItem->id);
                                                    $isUsed = $mark?->is_used ?? false;
                                                ?>
                                                <label <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'usage-'.e($batch->id).'-'.e($setItem->id).''; ?>wire:key="usage-<?php echo e($batch->id); ?>-<?php echo e($setItem->id); ?>"
                                                       class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" <?php if($isUsed): echo 'checked'; endif; ?>
                                                           wire:click="toggleSetItemUsage(<?php echo e($batch->id); ?>, <?php echo e($setItem->id); ?>)"
                                                           class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                                    <?php echo e($setItem->name); ?>

                                                    <span class="text-xs text-slate-400">(<?php echo e($setItem->pivot->quantity); ?>x)</span>
                                                </label>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div :class="tab === 'scan' ? 'block' : 'hidden lg:block'">
            <div class="card p-5">
                <?php if (isset($component)) { $__componentOriginalc5ce3e29d99ca290c1aa66ef2e171a51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc5ce3e29d99ca290c1aa66ef2e171a51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan-input','data' => ['submit' => 'scanUsage','label' => 'Scan alat yang mulai dipakai','placeholder' => 'CSSD-XXXXXXXX']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['submit' => 'scanUsage','label' => 'Scan alat yang mulai dipakai','placeholder' => 'CSSD-XXXXXXXX']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc5ce3e29d99ca290c1aa66ef2e171a51)): ?>
<?php $attributes = $__attributesOriginalc5ce3e29d99ca290c1aa66ef2e171a51; ?>
<?php unset($__attributesOriginalc5ce3e29d99ca290c1aa66ef2e171a51); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc5ce3e29d99ca290c1aa66ef2e171a51)): ?>
<?php $component = $__componentOriginalc5ce3e29d99ca290c1aa66ef2e171a51; ?>
<?php unset($__componentOriginalc5ce3e29d99ca290c1aa66ef2e171a51); ?>
<?php endif; ?>
                <p class="mt-3 text-xs text-slate-500">
                    Scan barcode satu alat (atau satu set) untuk langsung menandainya "sedang dipakai".
                    Untuk menandai sebagian isi satu set saja, gunakan checklist di tabel sebelah.
                </p>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/livewire/unit/unit-inventory.blade.php ENDPATH**/ ?>