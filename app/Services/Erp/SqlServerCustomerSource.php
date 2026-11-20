<?php

namespace App\Services\Erp;

use App\Contracts\ErpCustomerSource;
use Illuminate\Support\Facades\DB;

/** Đọc bảng dbo.COPMA của ERP qua connection 'erp'. Chỉ SELECT, không bao giờ ghi vào ERP. */
class SqlServerCustomerSource implements ErpCustomerSource
{
    public function chunks(int $size): iterable
    {
        OdbcSqlServerConnector::assertAvailable();

        $lastKey = null;
        while (true) {
            // Phân trang theo khóa (keyset) để không phụ thuộc OFFSET và không bỏ sót dòng.
            $rows = DB::connection('erp')->table('COPMA')
                ->when($lastKey !== null, fn ($q) => $q->where('MA001', '>', $lastKey))
                ->orderBy('MA001')
                ->limit($size)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            if ($rows === []) {
                return;
            }

            yield $rows;
            $lastKey = end($rows)['MA001'];
        }
    }
}
