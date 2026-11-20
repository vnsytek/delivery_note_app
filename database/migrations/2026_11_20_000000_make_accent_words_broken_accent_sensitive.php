<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * accent_words.broken phải phân biệt dấu: "THÀNH PH?" và "THANH PH?" là hai mục khác nhau (app so khóa đúng từng chữ
 * trong PHP). Với utf8mb4_unicode_ci MySQL coi chúng là trùng nên accent:suggest bị lỗi "Duplicate entry".
 * Chỉ áp dụng cho MySQL/MariaDB (SQLite của bộ test vốn đã so đúng từng byte).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->collation('utf8mb4_bin');
    }

    public function down(): void
    {
        $this->collation('utf8mb4_unicode_ci');
    }

    private function collation(string $collation): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        Schema::table('accent_words', function (Blueprint $table) use ($collation) {
            $table->string('broken', 120)->collation($collation)->change();
        });
    }
};
