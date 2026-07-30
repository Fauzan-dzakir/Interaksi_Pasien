{{-- Notifikasi pop-up global (dipicu lewat $this->dispatch('toast', type: ..., message: ...)
     dari komponen Livewire mana pun, mis. saat scan berhasil/gagal). Diletakkan sekali di
     layout utama supaya muncul di atas layar — riwayat/log di halaman tetap ada seperti biasa,
     pop-up ini cuma tambahan supaya kelihatan di HP tanpa perlu scroll ke bawah. --}}
<div x-data="toastStack()" x-on:toast.window="push($event.detail)"
     class="pointer-events-none fixed inset-x-0 top-2 z-[100] flex flex-col items-center gap-2 px-4 sm:top-4 print:hidden">
    <template x-for="t in toasts" :key="t.id">
        <div x-show="t.visible" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto w-full max-w-sm rounded-lg border px-4 py-3 text-sm font-medium shadow-lg"
             :class="{
                'border-emerald-300 bg-emerald-50 text-emerald-800': t.type === 'success',
                'border-red-300 bg-red-50 text-red-700': t.type === 'error',
                'border-slate-300 bg-slate-50 text-slate-700': t.type === 'info',
             }">
            <span x-text="t.message"></span>
        </div>
    </template>
</div>

{{-- Bukan @script/@endscript (itu khusus komponen Livewire, butuh $this = instance
     komponen). Komponen ini dipasang dari layout statis (bukan dari dalam render
     Livewire), jadi didaftarkan lewat event alpine:init biasa. --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastStack', () => ({
            toasts: [],

            push(detail) {
                if (! detail?.message) return;

                const id = Date.now() + Math.random();
                this.toasts.push({ id, type: detail.type || 'info', message: detail.message, visible: true });

                // Toast error dibiarkan sedikit lebih lama supaya sempat terbaca.
                const duration = detail.type === 'error' ? 5000 : 3200;

                setTimeout(() => {
                    const t = this.toasts.find(t => t.id === id);
                    if (t) t.visible = false;
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 200);
                }, duration);
            },
        }));
    });
</script>
