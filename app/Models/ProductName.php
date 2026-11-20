<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tên hàng sửa tay theo mã hàng: tên Việt đúng dấu (name), tên tiếng Trung (name_zh), quy cách Việt/Trung (spec, spec_zh).
 * Trường để trống = dùng bản tự động từ dữ liệu ERP (xem App\Services\ProductLineText).
 */
class ProductName extends Model
{
    protected $fillable = ['item_code', 'name', 'name_zh', 'spec', 'spec_zh'];
}
