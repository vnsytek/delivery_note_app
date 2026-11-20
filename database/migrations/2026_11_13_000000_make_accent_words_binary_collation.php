<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 'broken' phải phân biệt chữ có dấu: với collation mặc định utf8mb4_unicode_ci, "?ÈN" và "?EN" bị coi là
 * một khóa nên lệnh accent:suggest báo trùng khóa trên MySQL/MariaDB. Chuyển sang utf8mb4_bin (so sánh theo byte).
 * SQLite (test) vốn đã phân biệt nên bỏ qua.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        DB::statement('ALTER TABLE accent_words MODIFY broken VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        DB::statement('ALTER TABLE accent_words MODIFY fixed VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        DB::statement('ALTER TABLE accent_words MODIFY broken VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE accent_words MODIFY fixed VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }
};
