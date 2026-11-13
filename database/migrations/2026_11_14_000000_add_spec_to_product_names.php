<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quy cách sửa tay theo mã hàng (quy cách ERP cũng có thể mất dấu '?'). Để trống = dùng quy cách ERP đã gợi ý dấu.
 * Chỉ nằm trong MySQL của app; ERP không bị đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            $table->string('spec')->nullable()->after('name_zh');
        });
    }

    public function down(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            $table->dropColumn('spec');
        });
    }
};
