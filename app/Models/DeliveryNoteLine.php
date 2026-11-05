<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id', 'seq', 'item_code', 'erp_name', 'erp_spec', 'quantity', 'unit_raw',
        'customer_po', 'customer_item_code', 'dept_code', 'remark',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
