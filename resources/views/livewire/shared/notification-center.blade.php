<div wire:poll.30s>
    <x-page-header title="Notifikasi" :subtitle="$unreadCount . ' notifikasi belum dibaca.'">
        <x-slot:actions>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="btn-secondary">Tandai semua dibaca</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <li wire:key="n-{{ $notification->id }}"
                    @class(['flex items-start gap-4 px-5 py-4', 'bg-sky-50/50' => ! $notification->read_at])>
                    <span @class([
                        'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                        'bg-sky-500' => ! $notification->read_at,
                        'bg-slate-200' => $notification->read_at,
                    ])></span>

                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-slate-900">{{ $data['title'] ?? 'Notifikasi' }}</div>
                        <div class="mt-0.5 text-sm text-slate-600">{{ $data['message'] ?? '' }}</div>
                        <div class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if (! empty($data['url']))
                            <a href="{{ $data['url'] }}" wire:navigate class="btn-secondary !px-3 !py-1.5">Buka</a>
                        @endif
                        @unless ($notification->read_at)
                            <button wire:click="markAsRead('{{ $notification->id }}')"
                                    class="text-xs text-slate-500 hover:text-slate-700">Tandai dibaca</button>
                        @endunless
                    </div>
                </li>
            @empty
                <li class="px-5 py-12 text-center">
                    <div class="text-sm font-medium text-slate-600">Belum ada notifikasi</div>
                    <div class="mt-1 text-sm text-slate-400">
                        Notifikasi muncul saat pesanan siap, kiriman alat kotor perlu dikonfirmasi,
                        atau alat selesai disterilkan.
                    </div>
                </li>
            @endforelse
        </ul>

        @if ($notifications->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
