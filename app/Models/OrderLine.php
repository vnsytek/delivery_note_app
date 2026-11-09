<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'seq', 'item_code', 'erp_name', 'erp_spec', 'quantity', 'delivered_quantity', 'unit_raw',
        'unit_price', 'amount', 'due_on', 'customer_item_code', 'closed_code',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'quantity' => 'float',
            'delivered_quantity' => 'float',
            'unit_price' => 'float',
            'amount' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Đã giao đủ, hoặc đơn đã được đóng dòng này (Y = tự đóng, y = đóng tay). */
    public function isDone(): bool
    {
        return ($this->quantity > 0 && $this->delivered_quantity >= $this->quantity) || in_array($this->closed_code, ['Y', 'y'], true);
    }
}
