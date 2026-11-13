<?php

namespace App\Console\Commands;

use App\Services\AccentSuggester;
use Illuminate\Console\Command;

class SuggestAccents extends Command
{
    protected $signature = 'accent:suggest';

    protected $description = 'Tự đoán dấu cho các từ ERP bị mất dấu (\'?\') và cập nhật Từ điển sửa dấu. Chỉ ghi vào MySQL của app; không đụng ERP.';

    public function handle(AccentSuggester $suggester): int
    {
        $r = $suggester->run();

        $this->info("{$r['words']} từ lỗi: {$r['confirmed']} đã xác nhận, {$r['suggested']} tự sửa (gợi ý), {$r['unsure']} chờ người chọn.");
        if ($r['learned']) {
            $this->line("Học thêm {$r['learned']} từ từ các lần sửa tay.");
        }

        return self::SUCCESS;
    }
}
