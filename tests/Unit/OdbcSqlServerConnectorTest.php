<?php

namespace Tests\Unit;

use App\Services\Erp\OdbcSqlServerConnector;
use PHPUnit\Framework\TestCase;

class OdbcSqlServerConnectorTest extends TestCase
{
    private const BASE = ['host' => '172.20.33.88', 'port' => '1433', 'database' => 'SY2019A'];

    public function test_windows_sql_server_driver_dsn_is_unchanged(): void
    {
        $this->assertSame(
            'odbc:Driver={SQL Server};Server=172.20.33.88,1433;Database=SY2019A;AutoTranslate=no',
            OdbcSqlServerConnector::dsn(self::BASE + ['odbc_driver' => 'SQL Server', 'encrypt' => 'yes', 'trust_server_certificate' => 'true']),
        );
    }

    public function test_msodbcsql_driver_gets_encryption_and_trust_flags(): void
    {
        $dsn = OdbcSqlServerConnector::dsn(self::BASE + [
            'odbc_driver' => 'ODBC Driver 18 for SQL Server', 'encrypt' => 'yes', 'trust_server_certificate' => 'true',
        ]);

        $this->assertStringStartsWith('odbc:Driver={ODBC Driver 18 for SQL Server};Server=172.20.33.88,1433;', $dsn);
        $this->assertStringEndsWith(';Encrypt=yes;TrustServerCertificate=yes', $dsn);
    }

    public function test_msodbcsql_defaults_and_value_mapping(): void
    {
        $this->assertStringEndsWith(
            ';Encrypt=yes;TrustServerCertificate=no',
            OdbcSqlServerConnector::dsn(self::BASE + ['odbc_driver' => 'ODBC Driver 17 for SQL Server']),
        );
        $this->assertStringEndsWith(
            ';Encrypt=no;TrustServerCertificate=no',
            OdbcSqlServerConnector::dsn(self::BASE + ['odbc_driver' => 'ODBC Driver 18 for SQL Server', 'encrypt' => 'false', 'trust_server_certificate' => '0']),
        );
        $this->assertStringEndsWith(
            ';Encrypt=strict;TrustServerCertificate=yes',
            OdbcSqlServerConnector::dsn(self::BASE + ['odbc_driver' => 'ODBC Driver 18 for SQL Server', 'encrypt' => 'Strict', 'trust_server_certificate' => true]),
        );
    }
}
