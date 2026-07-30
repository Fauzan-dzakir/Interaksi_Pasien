<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_batch_id', 'stage', 'item_key', 'item_label', 'is_present', 'note', 'recorded_by_user_id', 'recorded_at'])]
class ItemBatchStageCheck extends Model
{
    protected function casts(): array
    {
        return [
            'is_present' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
