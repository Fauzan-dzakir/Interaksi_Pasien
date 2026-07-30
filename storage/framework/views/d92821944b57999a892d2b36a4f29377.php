<div wire:poll.20s>
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'Telusur Alat','subtitle' => 'Cari posisi terakhir alat beserta bukti waktu dan petugasnya.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Telusur Alat','subtitle' => 'Cari posisi terakhir alat beserta bukti waktu dan petugasnya.']); ?>
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

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <div class="text-sm text-slate-500">Alat Ditandai Hilang</div>
            <div class="mt-1 text-3xl font-semibold <?php echo e($lostCount > 0 ? 'text-red-600' : 'text-slate-900'); ?>">
                <?php echo e($lostCount); ?>

            </div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Koreksi Admin Tercatat</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900"><?php echo e($overrideCount); ?></div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Hasil Pencarian</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900"><?php echo e($batches->total()); ?></div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="field-label" for="q">Kode label / nama alat / nomor order</label>
                <input wire:model.live.debounce.300ms="search" id="q" type="search"
                       class="field-input" placeholder="mis. CSSD-7F3K9M2P atau Set Bedah Minor">
            </div>

            <div>
                <label class="field-label" for="f-unit">Unit</label>
                <?php if (isset($component)) { $__componentOriginal391e5bef920d393958d3dc69b840c47c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391e5bef920d393958d3dc69b840c47c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tom-select','data' => ['wireModel' => 'filterUnit','placeholder' => 'Semua unit','searchPlaceholder' => 'Cari unit…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire-model' => 'filterUnit','placeholder' => 'Semua unit','search-placeholder' => 'Cari unit…']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $unitOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($unit->id); ?>" <?php if($filterUnit == $unit->id): echo 'selected'; endif; ?>><?php echo e($unit->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $attributes = $__attributesOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__attributesOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $component = $__componentOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__componentOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
            </div>

            <div>
                <label class="field-label" for="f-from">Dari tanggal</label>
                <input wire:model.live="from" id="f-from" type="date" class="field-input">
            </div>

            <div>
                <label class="field-label" for="f-to">Sampai tanggal</label>
                <input wire:model.live="to" id="f-to" type="date" class="field-input">
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 border-t border-slate-200 p-4">
            <div class="flex-1 sm:max-w-xs">
                <label class="field-label" for="f-status">Status</label>
                <?php if (isset($component)) { $__componentOriginal391e5bef920d393958d3dc69b840c47c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391e5bef920d393958d3dc69b840c47c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tom-select','data' => ['wireModel' => 'filterStatus','placeholder' => 'Semua status','searchPlaceholder' => 'Cari status…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire-model' => 'filterStatus','placeholder' => 'Semua status','search-placeholder' => 'Cari status…']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($value); ?>" <?php if($filterStatus === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $attributes = $__attributesOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__attributesOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $component = $__componentOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__componentOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
            </div>
            <div class="flex-1 sm:max-w-xs">
                <label class="field-label" for="f-zone">Zona</label>
                <?php if (isset($component)) { $__componentOriginal391e5bef920d393958d3dc69b840c47c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal391e5bef920d393958d3dc69b840c47c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tom-select','data' => ['wireModel' => 'filterZone','placeholder' => 'Semua zona','searchPlaceholder' => 'Cari zona…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire-model' => 'filterZone','placeholder' => 'Semua zona','search-placeholder' => 'Cari zona…']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $zoneOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($value); ?>" <?php if($filterZone === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $attributes = $__attributesOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__attributesOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal391e5bef920d393958d3dc69b840c47c)): ?>
<?php $component = $__componentOriginal391e5bef920d393958d3dc69b840c47c; ?>
<?php unset($__componentOriginal391e5bef920d393958d3dc69b840c47c); ?>
<?php endif; ?>
            </div>
            <button wire:click="resetFilters" class="btn-secondary">Reset Filter</button>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Kode Label</th>
                            <th>Alat / Set</th>
                            <th>Unit</th>
                            <th>Status Terakhir</th>
                            <th>Waktu</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'ab-'.e($batch->id).''; ?>wire:key="ab-<?php echo e($batch->id); ?>">
                                <td class="font-mono text-xs text-slate-600"><?php echo e($batch->public_code); ?></td>
                                <td>
                                    <div class="font-medium text-slate-900"><?php echo e($batch->displayName()); ?></div>
                                    <div class="text-xs text-slate-400"><?php echo e($batch->displayQuantity()); ?></div>
                                </td>
                                <td><?php echo e($batch->originUnit->name); ?></td>
                                <td><?php if (isset($component)) { $__componentOriginale159a51f7801357ffeb98918a88212e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale159a51f7801357ffeb98918a88212e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.state-pill','data' => ['state' => $batch->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('state-pill'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['state' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->status)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale159a51f7801357ffeb98918a88212e7)): ?>
<?php $attributes = $__attributesOriginale159a51f7801357ffeb98918a88212e7; ?>
<?php unset($__attributesOriginale159a51f7801357ffeb98918a88212e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale159a51f7801357ffeb98918a88212e7)): ?>
<?php $component = $__componentOriginale159a51f7801357ffeb98918a88212e7; ?>
<?php unset($__componentOriginale159a51f7801357ffeb98918a88212e7); ?>
<?php endif; ?></td>
                                <td class="text-xs text-slate-500"><?php echo e($batch->status_changed_at->format('d/m/Y H:i')); ?></td>
                                <td class="text-right">
                                    <a href="<?php echo e(route('batches.show', $batch->id)); ?>" wire:navigate
                                       class="btn-secondary !px-3 !py-1.5">Riwayat</a>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['colspan' => '6','title' => 'Tidak ada alat yang cocok','description' => 'Coba ubah kata kunci atau reset filter.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '6','title' => 'Tidak ada alat yang cocok','description' => 'Coba ubah kata kunci atau reset filter.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batches->hasPages()): ?>
                <div class="border-t border-slate-200 p-4"><?php echo e($batches->links()); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="card p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Koreksi Admin Terakhir</h2>
            <p class="mb-3 text-xs text-slate-500">
                Setiap koreksi yang melompati alur normal tercatat di sini.
            </p>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentOverrides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'ov-'.e($event->id).''; ?>wire:key="ov-<?php echo e($event->id); ?>" class="mb-2 rounded-lg border border-red-200 bg-red-50/50 px-3 py-2">
                    <a href="<?php echo e(route('batches.show', $event->item_batch_id)); ?>" wire:navigate
                       class="font-mono text-xs font-medium text-slate-700 hover:text-teal-700">
                        <?php echo e($event->itemBatch->public_code); ?>

                    </a>
                    <div class="mt-0.5 text-xs text-slate-600">→ <?php echo e($event->to_status->label()); ?></div>
                    <div class="mt-0.5 text-xs text-slate-400">
                        <?php echo e($event->occurred_at->format('d/m H:i')); ?> · <?php echo e($event->actorName()); ?>

                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->note): ?>
                        <div class="mt-1 text-xs text-slate-500"><?php echo e($event->note); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <p class="text-sm text-slate-400">Belum ada koreksi manual.</p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/livewire/admin/audit-search.blade.php ENDPATH**/ ?>