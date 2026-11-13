<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đơn đặt hàng chép từ ERP (COPTC đầu đơn, COPTD dòng đơn), để xem/chụp gửi khách trước khi in phiếu giao.
 * Và dòng đơn gốc của mỗi dòng phiếu giao (COPTH.TH014-TH016) để nối đơn với phiếu giao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Số đơn ERP (TC001 + TC002), vd V221 + 261107003. Duy nhất.
            $table->string('erp_type', 4);
            $table->string('erp_no', 20);
            $table->date('ordered_on');                       // TC003
            $table->string('customer_code', 20)->index();     // TC004
            $table->string('customer_po', 60)->nullable();    // TC012
            $table->string('tax_type', 1)->default('');       // TC016: 1 giá đã gồm thuế, 2 chưa gồm thuế, 3/4/9 không thuế
            $table->decimal('tax_rate', 6, 4)->default(0);    // TC041
            $table->decimal('amount', 18, 2)->default(0);     // TC029 tiền hàng
            $table->decimal('tax', 18, 2)->default(0);        // TC030 thuế
            $table->string('confirm_code', 1)->default('');   // TC027: Y xác nhận, N chưa, V hủy
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['erp_type', 'erp_no']);
            $table->index('ordered_on');
            $table->foreign('customer_code')->references('ma001')->on('customers')->restrictOnDelete();
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('seq', 4);                          // TD003
            $table->string('item_code', 40);                   // TD004
            $table->string('erp_name')->nullable();            // TD005
            $table->string('erp_spec')->nullable();            // TD006
            $table->decimal('quantity', 15, 3)->default(0);            // TD008
            $table->decimal('delivered_quantity', 15, 3)->default(0);  // TD009
            $table->string('unit_raw', 20)->nullable();        // TD010
            $table->decimal('unit_price', 18, 4)->default(0);  // TD011
            $table->decimal('amount', 18, 2)->default(0);      // TD012
            $table->date('due_on')->nullable();                // TD013 ngày hẹn giao
            $table->string('customer_item_code', 60)->nullable(); // TD014
            $table->string('closed_code', 1)->default('');     // TD016: N chưa đóng, Y/y đã đóng
            $table->timestamps();

            $table->unique(['order_id', 'seq']);
        });

        // Dòng phiếu giao được lập từ dòng đơn nào (COPTH.TH014-TH016).
        Schema::table('delivery_note_lines', function (Blueprint $table) {
            $table->string('order_type', 4)->nullable()->after('customer_item_code');
            $table->string('order_no', 20)->nullable()->after('order_type');
            $table->string('order_seq', 4)->nullable()->after('order_no');
            $table->index(['order_type', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_note_lines', function (Blueprint $table) {
            $table->dropIndex(['order_type', 'order_no']);
            $table->dropColumn(['order_type', 'order_no', 'order_seq']);
        });
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
