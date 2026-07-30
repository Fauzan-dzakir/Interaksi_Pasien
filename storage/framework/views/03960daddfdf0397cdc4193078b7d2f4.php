<div wire:poll.15s>
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'Stasiun Scan','subtitle' => 'Pindai label QR untuk memindahkan alat ke tahap berikutnya.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Stasiun Scan','subtitle' => 'Pindai label QR untuk memindahkan alat ke tahap berikutnya.']); ?>
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

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5">
                <label class="field-label" for="station">Tahap / Stasiun</label>
                <select wire:model.live="station" id="station" class="field-input">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stationOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($opt->value); ?>"><?php echo e($opt->label()); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <p class="mt-2 text-sm text-slate-500"><?php echo e($stationEnum->description()); ?></p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-slate-500">Alat akan berpindah ke:</span>
                    <?php if (isset($component)) { $__componentOriginale159a51f7801357ffeb98918a88212e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale159a51f7801357ffeb98918a88212e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.state-pill','data' => ['state' => $stationEnum->targetStatus()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('state-pill'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['state' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stationEnum->targetStatus())]); ?>
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
<?php endif; ?>
                </div>
            </div>

            
            <div class="card p-5">
                <?php if (isset($component)) { $__componentOriginalc5ce3e29d99ca290c1aa66ef2e171a51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc5ce3e29d99ca290c1aa66ef2e171a51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.scan-input','data' => ['submit' => 'handleScan(null, \'hid_scanner\')','onCamera' => '$wire.handleScan(code, \'qr_camera\')','label' => 'Scan atau ketik kode label','inputClass' => 'field-input font-mono text-lg tracking-wider']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('scan-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['submit' => 'handleScan(null, \'hid_scanner\')','on-camera' => '$wire.handleScan(code, \'qr_camera\')','label' => 'Scan atau ketik kode label','input-class' => 'field-input font-mono text-lg tracking-wider']); ?>
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

                <p class="mt-2 text-xs text-slate-400">
                    Scanner fisik cukup diarahkan ke label — kode terisi dan terkirim otomatis.
                    Kolom ini juga bisa diketik manual bila label rusak.
                </p>
            </div>

            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Scan Sesi Ini</h2>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($log) > 0): ?>
                        <button wire:click="$set('log', [])" class="text-xs text-slate-500 hover:text-slate-700">Bersihkan</button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <ul class="divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $log; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <li class="flex items-start gap-3 px-5 py-3">
                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $entry['status'] === 'success',
                                'bg-red-100 text-red-700' => $entry['status'] === 'error',
                                'bg-slate-100 text-slate-500' => $entry['status'] === 'info',
                            ]); ?>">
                                <?php echo e($entry['status'] === 'success' ? '✓' : ($entry['status'] === 'error' ? '!' : '·')); ?>

                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="font-mono text-xs text-slate-500"><?php echo e($entry['code']); ?></div>
                                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'text-sm',
                                    'text-slate-800' => $entry['status'] !== 'error',
                                    'font-medium text-red-700' => $entry['status'] === 'error',
                                ]); ?>"><?php echo e($entry['message']); ?></div>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400"><?php echo e($entry['at']); ?></span>
                        </li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <li class="px-5 py-10 text-center text-sm text-slate-400">
                            Belum ada scan. Arahkan scanner ke label QR untuk mulai.
                        </li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Antrian Tahap Ini</h2>
                <div class="space-y-3">
                    <div class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-200">
                        <div class="text-xs font-medium text-amber-800">Menunggu masuk tahap ini</div>
                        <div class="mt-0.5 text-2xl font-semibold text-amber-900"><?php echo e($waitingCount); ?></div>
                    </div>
                    <div class="rounded-lg bg-teal-50 p-4 ring-1 ring-teal-200">
                        <div class="text-xs font-medium text-teal-800">Sedang di tahap ini</div>
                        <div class="mt-0.5 text-2xl font-semibold text-teal-900"><?php echo e($atStationCount); ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-slate-200 bg-slate-50/60 p-5">
                <h2 class="text-sm font-semibold text-slate-900">Kalau scan ditolak</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Sistem menolak alat yang belum melewati tahap sebelumnya. Ini disengaja —
                    alat yang belum selesai dicuci tidak boleh tercatat steril.
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    Cek status alat lewat menu <strong>Ganti Barcode</strong> atau halaman detail alat.
                    Bila memang ada salah scan sebelumnya, minta Admin melakukan koreksi.
                </p>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/livewire/cssd/scan-station.blade.php ENDPATH**/ ?>