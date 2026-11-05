<?php

namespace App\Services\Erp;

use App\Contracts\ErpCustomerSource;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Đọc bảng dbo.COPMA của ERP qua connection 'erp'. Chỉ SELECT, không bao giờ ghi vào ERP. */
class SqlServerCustomerSource implements ErpCustomerSource
{
    public function chunks(int $size): iterable
    {
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new RuntimeException(
                'Thiếu extension pdo_sqlsrv của PHP. Cài Microsoft Drivers for PHP for SQL Server '
                .'và bật extension=pdo_sqlsrv trong php.ini (xem README).'
            );
        }

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
