<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Từ điển sửa dấu theo TỪ: mỗi từ ERP bị mất dấu ('?') chỉ cần xác nhận một lần, app tự sửa ở mọi nơi.
 * Chỉ nằm trong MySQL của app; ERP không bị đụng tới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accent_words', function (Blueprint $table) {
            $table->id();
            $table->string('broken', 120)->unique();       // từ (hoặc cụm từ) như trong ERP, viết HOA: VI?T
            $table->string('fixed', 120)->nullable();      // dạng đúng, viết HOA: VIỆT
            $table->string('status', 12)->index();         // confirmed | suggested | unsure
            $table->string('source', 12)->default('auto'); // auto | learned | manual
            $table->json('candidates')->nullable();        // các phương án [{word, score}]
            $table->unsignedInteger('occurrences')->default(0);
            $table->string('example', 255)->nullable();    // một đoạn chữ ERP có từ này
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accent_words');
    }
};
