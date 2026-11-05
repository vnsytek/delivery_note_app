<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            // Số phiếu bán hàng của ERP (COPTG.TG001 + TG002), vd V231 + 261028005. Duy nhất => không thể trùng.
            $table->string('erp_type', 4);
            $table->string('erp_no', 20);
            $table->date('issued_on');
            $table->string('customer_code', 20)->index();
            $table->string('erp_customer_name')->nullable();
            $table->string('erp_address', 500)->nullable();
            // Các cột nhập tay: lệnh đồng bộ ERP KHÔNG ghi đè.
            $table->string('receiver', 100)->nullable();
            $table->string('supplier_code', 50)->nullable();
            $table->string('address_manual', 500)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['erp_type', 'erp_no']);
            $table->index('issued_on');
            $table->foreign('customer_code')->references('ma001')->on('customers')->restrictOnDelete();
        });

        Schema::create('delivery_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $table->string('seq', 4);
            $table->string('item_code', 40)->index();
            $table->string('erp_name')->nullable();
            $table->string('erp_spec')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('unit_raw', 20)->nullable();
            $table->string('customer_po', 60)->nullable();
            $table->string('customer_item_code', 60)->nullable();
            // Cột nhập tay (không bị đồng bộ ghi đè).
            $table->string('dept_code', 30)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['delivery_note_id', 'seq']);
        });

        // Tên hàng đúng dấu theo mã hàng, dùng chung cho mọi phiếu (bổ sung cho từ điển khôi phục dấu).
        Schema::create('product_names', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 40)->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_names');
        Schema::dropIfExists('delivery_note_lines');
        Schema::dropIfExists('delivery_notes');
    }
};
