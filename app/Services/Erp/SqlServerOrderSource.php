<?php

namespace App\Services\Erp;

use App\Contracts\ErpOrderSource;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/** Đọc COPTC (đầu đơn) + COPTD (dòng đơn) của ERP qua connection 'erp'. Chỉ SELECT. */
class SqlServerOrderSource implements ErpOrderSource
{
    public function chunks(string $since, int $size): iterable
    {
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new RuntimeException('Thiếu extension pdo_sqlsrv của PHP (xem README).');
        }
        if (! preg_match('/^\d{8}$/', $since)) {
            throw new InvalidArgumentException('Ngày --since phải có dạng YYYYMMDD.');
        }

        $conn = DB::connection('erp');
        $lastType = '';
        $lastNo = '';

        while (true) {
            // Phân trang theo khóa (TC001, TC002) để không phụ thuộc OFFSET.
            $headers = $conn->table('COPTC')
                ->select('TC001', 'TC002', 'TC003', 'TC004', 'TC012', 'TC016', 'TC027', 'TC029', 'TC030', 'TC041')
                ->whereIn('TC001', config('delivery_note.order_types'))
                ->where('TC003', '>=', $since)
                ->where(fn (Builder $q) => $q->where('TC001', '>', $lastType)
                    ->orWhere(fn (Builder $q) => $q->where('TC001', $lastType)->where('TC002', '>', $lastNo)))
                ->orderBy('TC001')->orderBy('TC002')
                ->limit($size)
                ->get();

            if ($headers->isEmpty()) {
                return;
            }

            $lines = $this->linesFor($conn, $headers);
            $orders = [];
            foreach ($headers as $h) {
                $type = trim($h->TC001);
                $no = trim($h->TC002);
                $orders[] = [
                    'type' => $type,
                    'no' => $no,
                    'date' => trim((string) $h->TC003),
                    'customer_code' => trim((string) $h->TC004),
                    'customer_po' => $h->TC012,
                    'tax_type' => $h->TC016,
                    'tax_rate' => $h->TC041,
                    'amount' => $h->TC029,
                    'tax' => $h->TC030,
                    'confirm_code' => $h->TC027,
                    'lines' => $lines["{$type}|{$no}"] ?? [],
                ];
            }
            yield $orders;

            $lastType = $headers->last()->TC001;
            $lastNo = $headers->last()->TC002;
        }
    }

    /** @return array<string, list<array<string, mixed>>> dòng đơn nhóm theo "TYPE|NO" */
    private function linesFor($conn, $headers): array
    {
        $rows = $conn->table('COPTD')
            ->select('TD001', 'TD002', 'TD003', 'TD004', 'TD005', 'TD006', 'TD008', 'TD009', 'TD010', 'TD011', 'TD012',
                'TD013', 'TD014', 'TD016')
            ->whereIn('TD001', $headers->pluck('TC001')->unique()->all())
            ->whereIn('TD002', $headers->pluck('TC002')->unique()->all())
            // Bỏ dòng đã hủy; dòng chưa có cờ (NULL) vẫn giữ.
            ->where(fn (Builder $q) => $q->whereNull('TD021')->orWhere('TD021', '<>', 'V'))
            ->orderBy('TD001')->orderBy('TD002')->orderBy('TD003')
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[trim($r->TD001).'|'.trim($r->TD002)][] = [
                'seq' => trim($r->TD003),
                'item_code' => trim((string) $r->TD004),
                'name' => $r->TD005,
                'spec' => $r->TD006,
                'quantity' => $r->TD008,
                'delivered_quantity' => $r->TD009,
                'unit' => $r->TD010,
                'unit_price' => $r->TD011,
                'amount' => $r->TD012,
                'due_date' => trim((string) $r->TD013),
                'customer_item_code' => $r->TD014,
                'closed_code' => $r->TD016,
            ];
        }

        return $grouped;
    }
}
