<?php

namespace App\Services\Erp;

use App\Contracts\ErpDeliveryNoteSource;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/** Đọc COPTG (đầu phiếu) + COPTH (dòng) của ERP qua connection 'erp'. Chỉ SELECT. */
class SqlServerDeliveryNoteSource implements ErpDeliveryNoteSource
{
    public function chunks(string $since, int $size): iterable
    {
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new RuntimeException('Thiếu extension pdo_sqlsrv của PHP (xem README).');
        }
        if (! preg_match('/^\d{8}$/', $since)) {
            throw new InvalidArgumentException('Ngày --since phải có dạng YYYYMMDD.');
        }

        $cfg = config('erp.delivery_note');
        $col = array_map([$this, 'column'], $cfg['columns']);
        $conn = DB::connection('erp');
        $lastType = '';
        $lastNo = '';

        while (true) {
            // Phân trang theo khóa (TG001, TG002) để không phụ thuộc OFFSET.
            $headers = $conn->table('COPTG')
                ->selectRaw("TG001, TG002, TG003, TG004, {$col['header_customer_name']} AS customer_name, {$col['header_address']} AS address")
                ->whereIn('TG001', $cfg['types'])
                ->where('TG003', '>=', $since)
                ->where(fn (Builder $q) => $q->where('TG001', '>', $lastType)
                    ->orWhere(fn (Builder $q) => $q->where('TG001', $lastType)->where('TG002', '>', $lastNo)))
                ->orderBy('TG001')->orderBy('TG002')
                ->limit($size)
                ->get();

            if ($headers->isEmpty()) {
                return;
            }

            $lines = $this->linesFor($conn, $headers, $col);
            $notes = [];
            foreach ($headers as $h) {
                $type = trim($h->TG001);
                $no = trim($h->TG002);
                $notes[] = [
                    'type' => $type,
                    'no' => $no,
                    'date' => trim($h->TG003),
                    'customer_code' => trim($h->TG004),
                    'customer_name' => $h->customer_name,
                    'address' => $h->address,
                    'lines' => $lines["{$type}|{$no}"] ?? [],
                ];
            }
            yield $notes;

            $lastType = $headers->last()->TG001;
            $lastNo = $headers->last()->TG002;
        }
    }

    /** @return array<string, list<array<string, mixed>>> dòng hàng nhóm theo "TYPE|NO" */
    private function linesFor($conn, $headers, array $col): array
    {
        // TD014 = mã hàng của khách, TC012 = số đơn (PO) của khách - lấy từ đơn đặt hàng gốc (TH014-TH016).
        $rows = $conn->table('COPTH as h')
            ->leftJoin('COPTD as d', fn ($j) => $j->on('d.TD001', '=', 'h.TH014')->on('d.TD002', '=', 'h.TH015')->on('d.TD003', '=', 'h.TH016'))
            ->leftJoin('COPTC as c', fn ($j) => $j->on('c.TC001', '=', 'h.TH014')->on('c.TC002', '=', 'h.TH015'))
            ->selectRaw("h.TH001, h.TH002, h.TH003, h.TH004, h.TH005, h.TH006, h.{$col['line_quantity']} AS quantity, h.{$col['line_unit']} AS unit, d.TD014 AS customer_item_code, c.TC012 AS customer_po, h.TH014, h.TH015, h.TH016")
            ->whereIn('h.TH001', $headers->pluck('TG001')->unique()->all())
            ->whereIn('h.TH002', $headers->pluck('TG002')->unique()->all())
            ->where('h.TH020', '<>', 'V') // bỏ dòng đã hủy
            ->orderBy('h.TH001')->orderBy('h.TH002')->orderBy('h.TH003')
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[trim($r->TH001).'|'.trim($r->TH002)][] = [
                'seq' => trim($r->TH003),
                'item_code' => trim($r->TH004),
                'name' => $r->TH005,
                'spec' => $r->TH006,
                'quantity' => $r->quantity,
                'unit' => $r->unit,
                'customer_po' => $r->customer_po,
                'customer_item_code' => $r->customer_item_code,
                // Dòng đơn đặt hàng gốc (để trang đơn hàng liệt kê phiếu giao của đơn).
                'order_type' => $r->TH014,
                'order_no' => $r->TH015,
                'order_seq' => $r->TH016,
            ];
        }

        return $grouped;
    }

    /** Tên cột lấy từ config được kiểm tra chặt trước khi ghép vào SQL. */
    private function column(string $name): string
    {
        if (! preg_match('/^[A-Z]{2}\d{3}$/', $name)) {
            throw new InvalidArgumentException("Tên cột ERP không hợp lệ: {$name}");
        }

        return $name;
    }
}
