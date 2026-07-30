<?php

namespace App\Livewire\Unit;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Services\ItemBatchTransitionService;
use App\Services\PublicCodeGenerator;
use Livewire\Component;

class ReceiptScan extends Component
{
    public string $code = '';
    
    public string $feedback = '';
    
    public string $feedbackType = '';
    
    public function scan(ItemBatchTransitionService $transitions): void
    {
        $code = PublicCodeGenerator::normalize($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $batch = ItemBatch::where('public_code', $code)->first();

        if (! $batch) {
            $this->flash('error', "Kode label {$code} tidak ditemukan.");
            return;
        }
        
        if ($batch->origin_unit_id !== auth()->user()->unit_id) {
            $this->flash('error', "Kode label {$code} bukan milik unit Anda.");
            return;
        }

        try {
            // Confirm receipt (transition to PickedUp)
            $changed = $transitions->transition(
                $batch,
                ItemBatchStatus::PickedUp,
                auth()->user(),
                ScanInputMethod::HidScanner,
                'Konfirmasi Penerimaan oleh Unit',
            );
        } catch (InvalidTransitionException $e) {
            $this->flash('error', $e->getMessage());
            return;
        }

        $this->flash(
            $changed ? 'success' : 'info',
            $changed
                ? "{$batch->displayName()} telah dikonfirmasi diterima di unit."
                : "{$code} sudah berstatus diterima."
        );
    }
    
    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
    }

    public function render()
    {
        return view('livewire.unit.receipt-scan');
    }
}
