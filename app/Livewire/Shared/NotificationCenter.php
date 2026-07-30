<?php

namespace App\Livewire\Shared;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        session()->flash('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function render()
    {
        return view('livewire.shared.notification-center', [
            'notifications' => auth()->user()->notifications()->paginate(20),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
