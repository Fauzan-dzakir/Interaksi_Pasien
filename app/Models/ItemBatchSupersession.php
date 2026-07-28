<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mencatat penggantian barcode lama menjadi barcode baru. */
#[Fillable(['old_item_batch_id', 'new_item_batch_id', 'created_by_user_id', 'reason'])]
class ItemBatchSupersession extends Model
{
    public function oldBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class, 'old_item_batch_id');
    }

    public function newBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class, 'new_item_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
