<?php

namespace App\Livewire\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use InvalidArgumentException;
use Livewire\Component;

class OrderShow extends Component
{
    public Order $order;

    public bool $showCancel = false;

    public string $cancelReason = '';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
    }

    /** Unit menyatakan alat sudah kembali, titik perpindahan tanggung jawab. */
    public function confirmReceipt(OrderService $service): void
    {
        $this->authorize('confirmReceipt', $this->order);

        $service->confirmReceipt($this->order, auth()->user());
        $this->order->refresh();

        session()->flash('status', 'Penerimaan dikonfirmasi. Alat kini tercatat berada di unit Anda.');
    }

    public function cancel(OrderService $service): void
    {
        $this->authorize('cancel', $this->order);

        $this->validate([
            'cancelReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['cancelReason' => 'alasan pembatalan']);

        try {
            $service->cancel($this->order, auth()->user(), $this->cancelReason);
        } catch (InvalidArgumentException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }

        $this->showCancel = false;
        $this->cancelReason = '';
        $this->order->refresh();

        session()->flash('status', 'Pesanan dibatalkan.');
    }

    /**
     * Empat tahap yang dilihat unit. Sengaja tidak sedetail status internal CSSD,
     * karena unit hanya perlu tahu sudah sampai mana pesanannya.
     *
     * @return array<int, array{label: string, state: string}>
     */
    private function progressSteps(): array
    {
        $status = $this->order->status;

        $reached = match ($status) {
            OrderStatus::Pending => 0,
            OrderStatus::Preparing => 1,
            OrderStatus::ReadyForPickup, OrderStatus::Delivering => 2,
            OrderStatus::Received => 3,
            OrderStatus::Cancelled => -1,
        };

        $labels = [
            'Pesanan dibuat',
            'Alat diterima dan diproses CSSD',
            'Selesai steril',
            'Kembali ke unit',
        ];

        return collect($labels)->map(fn (string $label, int $index) => [
            'label' => $label,
            'state' => match (true) {
                $reached < 0 => 'todo',
                $index < $reached => 'done',
                $index === $reached => $reached === 3 ? 'done' : 'current',
                default => 'todo',
            },
        ])->all();
    }

    public function render()
    {
        $this->order->load([
            'unit', 'requestedBy', 'batch', 'preparedBy', 'receivedBy',
            'photos', 'assets.instrumentSet', 'assets.item', 'events.actor',
        ]);

        return view('livewire.unit.order-show', [
            'canConfirm' => auth()->user()->can('confirmReceipt', $this->order),
            'progressSteps' => $this->progressSteps(),
        ]);
    }
}
