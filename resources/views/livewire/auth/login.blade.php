<div class="w-full max-w-md">
    <div class="mb-6 text-center">
        <img src="{{ asset('images/kemenkes-logo.png') }}" alt="RS Kemenkes Surabaya" class="mx-auto mb-4 h-12 w-auto">
        <h1 class="text-xl font-semibold text-slate-900">SIM Alat CSSD</h1>
        <p class="text-sm text-slate-500">RS Kemenkes Surabaya</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" id="email" type="email" required autofocus autocomplete="username"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Kata Sandi</label>
                <input wire:model="password" id="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input wire:model="remember" type="checkbox"
                       class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-60"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memproses…</span>
            </button>
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-slate-500">
        Akun dibuat oleh Admin. Hubungi Admin bila belum punya akses.
    </p>
</div>
