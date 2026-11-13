<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id', 'seq', 'item_code', 'erp_name', 'erp_spec', 'quantity', 'quantity_manual', 'unit_raw',
        'customer_po', 'customer_item_code', 'order_type', 'order_no', 'order_seq', 'dept_code', 'remark',
    ];

    protected function casts(): array
    {
        // quantity = số lượng ERP (đồng bộ ghi đè); quantity_manual = số sửa tay khi in (null = theo ERP).
        return ['quantity' => 'decimal:3', 'quantity_manual' => 'decimal:3'];
    }

    /** Số lượng in trên phiếu: số sửa tay nếu có, không thì số ERP. */
    public function printedQuantity(): float
    {
        return (float) ($this->quantity_manual ?? $this->quantity);
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
