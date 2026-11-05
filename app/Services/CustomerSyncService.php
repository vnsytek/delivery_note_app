<?php

namespace App\Services;

use App\Contracts\ErpCustomerSource;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ khách hàng từ ERP vào MySQL theo kiểu upsert: thêm mới/cập nhật dữ liệu gốc,
 * KHÔNG xóa khách hàng nào và KHÔNG đụng vào bản sửa tay (ma002_manual, ma003_manual).
 */
class CustomerSyncService
{
    private const CHUNK_SIZE = 500;

    /** Cột COPMA đã có cột riêng; mọi cột còn lại gom vào JSON `extra`. */
    private const DEDICATED_COLUMNS = ['MA001', 'MA002', 'MA003'];

    public function __construct(private readonly ErpCustomerSource $source) {}

    /** @return int số khách hàng đã đồng bộ */
    public function sync(): int
    {
        $now = now();
        $total = 0;

        foreach ($this->source->chunks(self::CHUNK_SIZE) as $rows) {
            $records = [];
            foreach ($rows as $row) {
                $code = trim((string) ($row['MA001'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $records[] = [
                    'ma001' => $code,
                    'ma002' => $this->text($row['MA002'] ?? null),
                    'ma003' => $this->text($row['MA003'] ?? null),
                    'extra' => $this->extraJson($row),
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Mỗi lô là một transaction: lỗi giữa chừng không để lại lô ghi dở.
            DB::transaction(fn () => Customer::upsert(
                $records,
                ['ma001'],
                ['ma002', 'ma003', 'extra', 'synced_at', 'updated_at'],
            ));
            $total += count($records);
        }

        return $total;
    }

    private function text(mixed $value): ?string
    {
        return $value === null ? null : rtrim((string) $value);
    }

    private function extraJson(array $row): string
    {
        $extra = [];
        foreach ($row as $column => $value) {
            if (in_array($column, self::DEDICATED_COLUMNS, true) || $value === null) {
                continue;
            }
            $value = is_string($value) ? trim($value) : $value;
            if ($value !== '') {
                $extra[$column] = $value;
            }
        }

        return json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
