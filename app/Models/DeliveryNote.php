<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'erp_type', 'erp_no', 'issued_on', 'customer_code', 'erp_customer_name', 'erp_address',
        'receiver', 'supplier_code', 'address_manual', 'note', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryNoteLine::class)->orderBy('seq');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'ma001');
    }

    /** Số phiếu hiển thị, vd V231-261028005. */
    public function number(): string
    {
        return $this->erp_type.'-'.$this->erp_no;
    }
}
