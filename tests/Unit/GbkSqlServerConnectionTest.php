<?php

namespace Tests\Unit;

use App\Services\Erp\GbkSqlServerConnection;
use PDO;
use PHPUnit\Framework\TestCase;

class GbkSqlServerConnectionTest extends TestCase
{
    public function test_decode_converts_gbk_bytes_to_utf8_and_keeps_ascii(): void
    {
        $gbk = mb_convert_encoding('CHILISIN奇力新', 'CP936', 'UTF-8');

        $this->assertSame('CHILISIN奇力新', GbkSqlServerConnection::decode($gbk));
        $this->assertSame('C?NG TY TNHH', GbkSqlServerConnection::decode('C?NG TY TNHH')); // chữ Việt mất dấu vẫn là '?'
        $this->assertSame('', GbkSqlServerConnection::decode(''));
        $this->assertSame('HDV', GbkSqlServerConnection::decode('HDV'));
    }

    public function test_select_decodes_every_string_column_of_every_row(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table copma (ma001 text, ma002 text, so integer)');
        $insert = $pdo->prepare('insert into copma values (?, ?, ?)');
        $insert->execute(['HDV', mb_convert_encoding('HDV 百能', 'CP936', 'UTF-8'), 7]);
        $insert->execute(['UDE', 'UDE', 8]);

        $connection = new GbkSqlServerConnection($pdo);
        $rows = $connection->select('select * from copma order by ma001');

        $this->assertSame('HDV 百能', $rows[0]->ma002);
        $this->assertSame(7, $rows[0]->so); // cột số không bị đụng tới
        $this->assertSame('UDE', $rows[1]->ma002);
    }
}
