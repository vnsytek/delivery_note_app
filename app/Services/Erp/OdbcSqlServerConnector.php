<?php

namespace App\Services\Erp;

use PDO;
use RuntimeException;

/** Tạo PDO tới SQL Server của ERP bằng pdo_odbc + driver ODBC "SQL Server" có sẵn của Windows. Chỉ dùng để đọc. */
final class OdbcSqlServerConnector
{
    public static function connect(array $config): PDO
    {
        self::assertAvailable();

        foreach (['host', 'database', 'username'] as $key) {
            if (($config[$key] ?? '') === '') {
                throw new RuntimeException('Chưa điền ERP_DB_'.strtoupper($key === 'username' ? 'USERNAME' : $key).' trong .env.');
            }
        }

        // AutoTranslate=no: nhận nguyên byte của cột char (GBK), GbkSqlServerConnection sẽ đổi sang UTF-8.
        $dsn = sprintf(
            'odbc:Driver={%s};Server=%s,%s;Database=%s;AutoTranslate=no',
            $config['odbc_driver'] ?? 'SQL Server',
            $config['host'],
            $config['port'] ?? '1433',
            $config['database'],
        );

        return new PDO($dsn, $config['username'], $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
    }

    /** Báo lỗi dễ hiểu nếu thiếu extension cần cho driver đang chọn (ERP_DB_DRIVER). */
    public static function assertAvailable(): void
    {
        $driver = config('database.connections.erp.driver');
        $needs = ['sqlsrv' => 'pdo_sqlsrv', 'erp_odbc' => 'pdo_odbc'][$driver] ?? null;
        if ($needs !== null && ! extension_loaded($needs)) {
            throw new RuntimeException(
                "Thiếu extension {$needs} của PHP (kết nối ERP đang dùng driver '{$driver}'). "
                ."Bật extension={$needs} trong php.ini rồi chạy lại (xem README, mục kết nối ERP)."
            );
        }
    }
}
