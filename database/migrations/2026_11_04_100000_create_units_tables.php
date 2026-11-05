<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh mục đơn vị tính chuẩn (cái, cuộn, bộ...).
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->timestamps();
        });

        // Các cách ghi khác nhau trong ERP/phiếu cũ (chiếc, 个, CÁI...) trỏ về một đơn vị chuẩn.
        Schema::create('unit_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('alias', 40)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_aliases');
        Schema::dropIfExists('units');
    }
};
