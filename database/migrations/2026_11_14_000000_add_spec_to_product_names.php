<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            // Quy cách tiếng Việt / tiếng Trung do người dùng sửa; để trống = dùng bản tự động (ERP + bảng từ).
            $table->string('spec')->nullable()->after('name_zh');
            $table->string('spec_zh')->nullable()->after('spec');
        });
    }

    public function down(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            $table->dropColumn(['spec', 'spec_zh']);
        });
    }
};
