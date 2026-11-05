<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tên hàng đúng dấu theo mã hàng. */
class ProductName extends Model
{
    protected $fillable = ['item_code', 'name'];
}
