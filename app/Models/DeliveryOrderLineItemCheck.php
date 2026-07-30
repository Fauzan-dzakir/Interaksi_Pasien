<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['delivery_order_line_id', 'item_id', 'is_present', 'note'])]
class DeliveryOrderLineItemCheck extends Model
{
    protected function casts(): array
    {
        return [
            'is_present' => 'boolean',
        ];
    }

    public function deliveryOrderLine(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
