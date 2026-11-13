<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Số lượng sửa tay khi in phiếu. Để trống = in theo số lượng ERP (cột quantity).
 * Chỉ nằm trong MySQL của app: đồng bộ ERP không ghi vào cột này, ERP cũng không bị đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_note_lines', function (Blueprint $table) {
            $table->decimal('quantity_manual', 15, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_note_lines', function (Blueprint $table) {
            $table->dropColumn('quantity_manual');
        });
    }
};
