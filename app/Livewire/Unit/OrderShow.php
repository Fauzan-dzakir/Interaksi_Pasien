<?php

namespace App\Livewire\Unit;

use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Services\DeliveryOrderService;
use Livewire\Component;

class OrderShow extends Component
{
    public DeliveryOrder $order;

    public string $cancelReason = '';

    public bool $showCancel = false;

    public function mount(DeliveryOrder $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
    }

    public function cancel(DeliveryOrderService $service): void
    {
        $this->authorize('cancel', $this->order);

        $this->validate([
            'cancelReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['cancelReason' => 'alasan pembatalan']);

        $service->cancel($this->order, auth()->user(), $this->cancelReason);

        $this->showCancel = false;
        $this->cancelReason = '';
        $this->order->refresh();

        session()->flash('status', 'Order dibatalkan.');
    }

    public function render()
    {
        $this->order->load(['originUnit', 'submittedBy', 'intakeRecordedBy', 'events.actor']);

        $batches = $this->order->detailVisibleToUnit()
            ? $this->order->itemBatches()->with(['instrumentSet', 'item'])->orderBy('public_code')->get()
            : collect();

        return view('livewire.unit.order-show', [
            'lines' => $this->order->detailVisibleToUnit()
                ? $this->order->lines()->with(['instrumentSet', 'item', 'recordedBy'])->get()
                : collect(),
            'batches' => $batches,
            'zoneSummary' => collect(ZoneBucket::cases())
                ->map(fn (ZoneBucket $zone) => [
                    'zone' => $zone,
                    'count' => $batches->filter(fn ($b) => $b->zone() === $zone)->count(),
                ])
                ->filter(fn (array $row) => $row['count'] > 0),
        ]);
    }
}
