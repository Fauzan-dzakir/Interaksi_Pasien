<?php

namespace App\Livewire\Cssd;

use App\Enums\BatchType;
use App\Models\DeliveryOrder;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Services\DeliveryOrderService;
use Livewire\Component;

/**
 * Pendataan fisik isi kiriman ("Pendataan Batch per Order dari Unit").
 * Dua jalur sesuai alur: Per Set atau Per Barang.
 */
class OrderIntake extends Component
{
    public DeliveryOrder $order;

    /** @var array<int, array{line_type: string, instrument_set_id: string, item_id: string, quantity: int, notes: string, set_checks: array}> */
    public array $lines = [];

    public function mount(DeliveryOrder $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;

        if (auth()->user()->can('recordIntake', $order)) {
            $this->addLine();
        }
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'line_type' => BatchType::Set->value,
            'instrument_set_id' => '',
            'item_id' => '',
            'quantity' => 1,
            'notes' => '',
            'set_checks' => [],
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Mengosongkan pilihan lawan saat jenis baris diganti, dan menyusun ulang
     * checklist isi set saat set yang dipilih berganti.
     * $key bernilai null bila seluruh array lines diganti sekaligus — abaikan kasus itu.
     */
    public function updatedLines($value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        if (str_ends_with($key, '.line_type')) {
            $index = (int) explode('.', $key)[0];

            $this->lines[$index]['instrument_set_id'] = '';
            $this->lines[$index]['item_id'] = '';
            $this->lines[$index]['set_checks'] = [];

            return;
        }

        if (str_ends_with($key, '.instrument_set_id')) {
            $index = (int) explode('.', $key)[0];

            $this->syncSetChecks($index, $value !== '' ? (int) $value : null);
        }
    }

    /** Mengisi checklist dengan daftar alat anggota set yang baru dipilih (default: semua dicentang). */
    private function syncSetChecks(int $index, ?int $setId): void
    {
        if (! $setId) {
            $this->lines[$index]['set_checks'] = [];

            return;
        }

        $set = InstrumentSet::with('items')->find($setId);

        $this->lines[$index]['set_checks'] = $set
            ? $set->items->map(fn (Item $item) => [
                'item_id' => $item->id,
                'name' => $item->name,
                'expected_quantity' => $item->pivot->quantity,
                'is_present' => true,
                'note' => '',
            ])->all()
            : [];
    }

    public function save(DeliveryOrderService $service): void
    {
        $this->authorize('recordIntake', $this->order);

        if ($this->lines === []) {
            $this->addError('lines', 'Tambahkan minimal satu baris pendataan.');

            return;
        }

        $this->validate($this->rules(), [], $this->validationAttributes());

        $payload = collect($this->lines)->map(fn (array $row) => [
            'line_type' => $row['line_type'],
            'instrument_set_id' => $row['line_type'] === BatchType::Set->value ? (int) $row['instrument_set_id'] : null,
            'item_id' => $row['line_type'] === BatchType::Individual->value ? (int) $row['item_id'] : null,
            'quantity' => (int) $row['quantity'],
            'notes' => $row['notes'] ?: null,
            'set_checks' => $row['line_type'] === BatchType::Set->value ? ($row['set_checks'] ?? []) : [],
        ])->all();

        $service->recordIntake($this->order, auth()->user(), $payload);

        $this->order->refresh();
        $this->lines = [];

        session()->flash('status', 'Pendataan tersimpan. Label QR sudah dibuat dan unit sudah diberi notifikasi.');
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        $rules = [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', 'in:set,individual'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            'lines.*.set_checks.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.set_checks.*.is_present' => ['boolean'],
            'lines.*.set_checks.*.note' => ['nullable', 'string', 'max:255'],
        ];

        // Kolom yang wajib diisi berbeda tergantung jalur pendataan tiap baris.
        foreach ($this->lines as $index => $row) {
            if (($row['line_type'] ?? null) === BatchType::Set->value) {
                $rules["lines.{$index}.instrument_set_id"] = ['required', 'exists:instrument_sets,id'];
            } else {
                $rules["lines.{$index}.item_id"] = ['required', 'exists:items,id'];
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        $attributes = [];

        foreach (array_keys($this->lines) as $index) {
            $no = $index + 1;
            $attributes["lines.{$index}.instrument_set_id"] = "set pada baris {$no}";
            $attributes["lines.{$index}.item_id"] = "alat pada baris {$no}";
            $attributes["lines.{$index}.quantity"] = "jumlah pada baris {$no}";
        }

        return $attributes;
    }

    public function render()
    {
        $this->order->load(['originUnit', 'submittedBy', 'intakeRecordedBy']);

        return view('livewire.cssd.order-intake', [
            'canRecord' => auth()->user()->can('recordIntake', $this->order),
            'setOptions' => InstrumentSet::active()->orderBy('name')->get(['id', 'code', 'name']),
            'itemOptions' => Item::active()->orderBy('code')->get(['id', 'code', 'name']),
            'recordedLines' => $this->order->lines()->with(['instrumentSet', 'item', 'recordedBy', 'itemChecks.item'])->get(),
            'batches' => $this->order->itemBatches()->with(['instrumentSet', 'item'])->orderBy('public_code')->get(),
            // Deklarasi unit saat membuat order — rujukan pembanding SAJA untuk hitung
            // fisik CSSD, bukan pengganti pendataan resmi (yang tetap dilakukan di bawah).
            'declaredBatches' => $this->order->declaredBatches()->with(['instrumentSet', 'item'])->get(),
        ]);
    }
}
