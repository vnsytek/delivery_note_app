<?php

namespace App\Services\Erp;

use Illuminate\Database\SqlServerConnection;

/**
 * Kết nối SQL Server của ERP qua pdo_odbc. Cột COPMA/COPTG... là char/varchar collation Chinese_PRC (cp936/GBK),
 * mà driver ODBC được cấu hình AutoTranslate=no nên PHP nhận nguyên byte GBK: đổi sang UTF-8 ở đây,
 * một chỗ duy nhất, để phần còn lại của app chỉ thấy chuỗi UTF-8 (chữ Hán nguyên vẹn, chữ Việt mất dấu vẫn là '?').
 */
class GbkSqlServerConnection extends SqlServerConnection
{
    private const ERP_ENCODING = 'CP936';

    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        return array_map(fn ($row) => $this->decodeRow($row), parent::select($query, $bindings, $useReadPdo, $fetchUsing));
    }

    public static function decode(string $value): string
    {
        // Chuỗi toàn ASCII (mã, số, ngày) giống hệt nhau ở mọi bảng mã nên bỏ qua cho nhanh.
        if ($value === '' || ! preg_match('/[\x80-\xFF]/', $value)) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', self::ERP_ENCODING);
    }

    private function decodeRow(mixed $row): mixed
    {
        if (is_object($row)) {
            foreach (get_object_vars($row) as $column => $value) {
                if (is_string($value)) {
                    $row->{$column} = self::decode($value);
                }
            }
        }

        return $row;
    }
}
