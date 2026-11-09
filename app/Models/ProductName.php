<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tên hàng theo mã hàng: tên Việt đúng dấu (name) và tên tiếng Trung (name_zh); trường để trống = dùng gợi ý tự động. */
class ProductName extends Model
{
    protected $fillable = ['item_code', 'name', 'name_zh'];
}
