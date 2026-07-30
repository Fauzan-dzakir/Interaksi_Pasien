
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'wireModel',
    'options',
    'placeholder' => 'Semua',
    'searchPlaceholder' => 'Ketik untuk mencari…',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'wireModel',
    'options',
    'placeholder' => 'Semua',
    'searchPlaceholder' => 'Ketik untuk mencari…',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div wire:ignore <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'ts-'.e($wireModel).''; ?>wire:key="ts-<?php echo e($wireModel); ?>" x-data="{
    init() {
        let ts = new TomSelect(this.$refs.select, {
            create: false,
            placeholder: <?php echo \Illuminate\Support\Js::from($searchPlaceholder)->toHtml() ?>,
            allowEmptyOption: true,
        });
        ts.on('change', (val) => { $wire.set('<?php echo e($wireModel); ?>', val); });
    }
}">
    <select x-ref="select" class="field-input">
        <option value=""><?php echo e($placeholder); ?></option>
        <?php echo e($slot); ?>

    </select>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/components/tom-select.blade.php ENDPATH**/ ?>