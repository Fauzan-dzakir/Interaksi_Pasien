<div>
    <x-page-header title="Scan Konfirmasi Penerimaan"
                   :subtitle="'Pindai QR alat steril untuk mengonfirmasi bahwa alat telah berada di ' . auth()->user()->unit->name . '.'" />

    <div class="card max-w-2xl p-8 mx-auto mt-8 text-center">
        <h2 class="text-xl font-bold text-slate-900">Konfirmasi Terima Alat</h2>
        <p class="mt-2 text-sm text-slate-500">
            Arahkan kursor ke dalam kotak di bawah ini dan pindai QR code alat menggunakan barcode scanner.
        </p>

        <form wire:submit="scan" class="mt-6">
            <input wire:model="code" type="text" autocomplete="off" autocapitalize="characters" autofocus
                   class="field-input font-mono tracking-wider text-center text-lg h-14" placeholder="Scan QR Code di sini...">
        </form>

        @if ($feedback)
            <div @class([
                'mt-6 p-4 rounded-lg border text-sm font-medium',
                'bg-emerald-50 border-emerald-200 text-emerald-800' => $feedbackType === 'success',
                'bg-red-50 border-red-200 text-red-800' => $feedbackType === 'error',
                'bg-sky-50 border-sky-200 text-sky-800' => $feedbackType === 'info',
            ])>
                {{ $feedback }}
            </div>
        @endif
        
        <div class="mt-8 text-xs text-slate-400">
            <p>Alat yang dikonfirmasi akan beralih status menjadi <strong>Sudah Diambil Unit</strong>.</p>
        </div>
    </div>
</div>
