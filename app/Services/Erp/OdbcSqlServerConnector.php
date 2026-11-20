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

        return new PDO(self::dsn($config), $config['username'], $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
    }

    /**
     * Chuỗi kết nối ODBC. AutoTranslate=no: nhận nguyên byte của cột char (GBK), GbkSqlServerConnection sẽ đổi sang UTF-8.
     * Driver "ODBC Driver 17/18 for SQL Server" (bắt buộc trên Linux) mặc định mã hóa và kiểm tra chứng chỉ, nên truyền
     * Encrypt / TrustServerCertificate theo ERP_DB_ENCRYPT / ERP_DB_TRUST_SERVER_CERTIFICATE (ERP dùng chứng chỉ tự cấp).
     * Driver "SQL Server" cũ của Windows giữ nguyên như trước.
     */
    public static function dsn(array $config): string
    {
        $driver = $config['odbc_driver'] ?? 'SQL Server';
        $dsn = sprintf(
            'odbc:Driver={%s};Server=%s,%s;Database=%s;AutoTranslate=no',
            $driver,
            $config['host'],
            $config['port'] ?? '1433',
            $config['database'],
        );

        if (stripos($driver, 'ODBC Driver') === 0) {
            $dsn .= ';Encrypt='.self::yesNo($config['encrypt'] ?? 'yes', keep: ['strict', 'mandatory', 'optional'])
                .';TrustServerCertificate='.self::yesNo($config['trust_server_certificate'] ?? 'false');
        }

        return $dsn;
    }

    /** true/1/yes/on -> yes, false/0/no/off -> no; các giá trị hợp lệ khác (vd strict) giữ nguyên. */
    private static function yesNo(mixed $value, array $keep = []): string
    {
        $value = strtolower(trim(is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value));

        return match (true) {
            in_array($value, ['1', 'true', 'yes', 'on'], true) => 'yes',
            in_array($value, $keep, true) => $value,
            default => 'no',
        };
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
