<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Mã khách hàng ERP (COPMA.MA001). Unique đồng thời là index cho ORDER BY / tra cứu.
            $table->string('ma001', 20)->unique();
            // Tên viết tắt / tên đầy đủ gốc từ ERP (có thể mất dấu tiếng Việt).
            $table->string('ma002')->nullable();
            $table->string('ma003', 500)->nullable();
            // Bản đúng dấu do người dùng sửa tay. Lệnh đồng bộ ERP KHÔNG ghi đè hai cột này.
            $table->string('ma002_manual')->nullable();
            $table->string('ma003_manual', 500)->nullable();
            // Các cột còn lại của COPMA, hiển thị ở phần chi tiết.
            $table->json('extra')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
