<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quy cách tiếng Trung sửa tay (spec_zh). Trên VPS cột này đã được tạo bởi bản 2026_11_14 sửa trực tiếp trên server,
 * còn bản 2026_11_14 trong repo chỉ thêm 'spec' - nên chỉ thêm khi chưa có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_names', 'spec_zh')) {
            return;
        }
        Schema::table('product_names', function (Blueprint $table) {
            $table->string('spec_zh')->nullable()->after('spec');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_names', 'spec_zh')) {
            Schema::table('product_names', function (Blueprint $table) {
                $table->dropColumn('spec_zh');
            });
        }
    }
};
