<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Khách hàng đồng bộ từ ERP (bảng COPMA). ma002/ma003 là dữ liệu gốc, *_manual là bản sửa tay.
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'ma001', 'ma002', 'ma003', 'ma002_manual', 'ma003_manual', 'extra', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ma001';
    }
}
