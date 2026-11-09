<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            // Tên tiếng Trung in ngay dưới tên tiếng Việt (song ngữ).
            $table->string('name_zh')->nullable()->after('name');
            // Một mục danh mục có thể chỉ có tên tiếng Trung; tên Việt để trống = dùng gợi ý tự động.
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_names', function (Blueprint $table) {
            $table->dropColumn('name_zh');
            $table->string('name')->nullable(false)->change();
        });
    }
};
