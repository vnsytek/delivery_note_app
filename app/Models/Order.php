<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Đơn đặt hàng chép từ ERP (COPTC + COPTD). Chỉ đọc: lệnh orders:sync-erp cập nhật. */
class Order extends Model
{
    use HasFactory;

    public const TAX_INCLUDED = '1';

    protected $fillable = [
        'erp_type', 'erp_no', 'ordered_on', 'customer_code', 'customer_po', 'tax_type', 'tax_rate',
        'amount', 'tax', 'confirm_code', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'ordered_on' => 'date',
            'synced_at' => 'datetime',
            'tax_rate' => 'float',
            'amount' => 'float',
            'tax' => 'float',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('seq');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'ma001');
    }

    /** Số đơn hiển thị, vd V221-261107003. */
    public function number(): string
    {
        return $this->erp_type.'-'.$this->erp_no;
    }

    public function isVoid(): bool
    {
        return $this->confirm_code === 'V';
    }

    /** Đơn giá trên các dòng đã gồm thuế (ERP: TC016 = 1). */
    public function pricesIncludeTax(): bool
    {
        return $this->tax_type === self::TAX_INCLUDED;
    }

    /** Phiếu giao có ít nhất một dòng lập từ đơn này. */
    public function deliveryNotes()
    {
        return DeliveryNote::query()
            ->whereHas('lines', fn ($q) => $q->where('order_type', $this->erp_type)->where('order_no', $this->erp_no))
            ->withCount('lines')
            ->orderBy('issued_on')->orderBy('erp_no');
    }
}
