<?php

namespace App\Services;

use App\Contracts\ErpOrderSource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ đơn đặt hàng ERP -> MySQL (upsert theo số đơn). Không xóa đơn; dòng đơn đi theo ERP.
 * Đơn không có cột nhập tay nên mọi cột đều lấy lại từ ERP mỗi lần.
 */
class OrderSyncService
{
    private const CHUNK_SIZE = 200;

    public function __construct(private readonly ErpOrderSource $source) {}

    /**
     * @return array{synced: int, invalid: int, unknown_customer: int}
     */
    public function sync(string $since): array
    {
        $result = ['synced' => 0, 'invalid' => 0, 'unknown_customer' => 0];

        foreach ($this->source->chunks($since, self::CHUNK_SIZE) as $chunk) {
            $knownCustomers = Customer::whereIn('ma001', array_column($chunk, 'customer_code'))->pluck('ma001')->all();
            $valid = [];
            foreach ($chunk as $order) {
                if (! $this->isValid($order)) {
                    $result['invalid']++;
                } elseif (! in_array($order['customer_code'], $knownCustomers, true)) {
                    $result['unknown_customer']++;
                } else {
                    $valid[] = $order;
                }
            }

            // Mỗi lô là một transaction: lỗi giữa chừng không để lại lô ghi dở.
            DB::transaction(fn () => $this->store($valid));
            $result['synced'] += count($valid);
        }

        return $result;
    }

    /** Số đơn: loại 4 ký tự, số 7-11 chữ số; ngày đặt là ngày có thật. */
    private function isValid(array $order): bool
    {
        return preg_match('/^[A-Z0-9]{4}$/', (string) $order['type']) === 1
            && preg_match('/^\d{7,11}$/', (string) $order['no']) === 1
            && $this->date($order['date']) !== null;
    }

    private function store(array $orders): void
    {
        if ($orders === []) {
            return;
        }
        $now = now();

        Order::upsert(
            array_map(fn (array $o) => [
                'erp_type' => $o['type'],
                'erp_no' => $o['no'],
                'ordered_on' => $this->date($o['date']),
                'customer_code' => $o['customer_code'],
                'customer_po' => $this->text($o['customer_po'], 60),
                'tax_type' => trim((string) $o['tax_type']),
                'tax_rate' => (float) $o['tax_rate'],
                'amount' => (float) $o['amount'],
                'tax' => (float) $o['tax'],
                'confirm_code' => trim((string) $o['confirm_code']),
                'synced_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ], $orders),
            ['erp_type', 'erp_no'],
            ['ordered_on', 'customer_code', 'customer_po', 'tax_type', 'tax_rate', 'amount', 'tax', 'confirm_code', 'synced_at', 'updated_at'],
        );

        // Một truy vấn lấy id của cả lô (không hỏi từng đơn).
        $ids = Order::query()
            ->whereIn('erp_no', array_column($orders, 'no'))
            ->get(['id', 'erp_type', 'erp_no'])
            ->mapWithKeys(fn (Order $o) => [$o->erp_type.'|'.$o->erp_no => $o->id]);

        foreach ($orders as $o) {
            $this->storeLines($ids[$o['type'].'|'.$o['no']], $o['lines'], $now);
        }
    }

    private function storeLines(int $orderId, array $lines, $now): void
    {
        $rows = array_map(fn (array $l) => [
            'order_id' => $orderId,
            'seq' => $l['seq'],
            'item_code' => $l['item_code'],
            'erp_name' => $this->text($l['name']),
            'erp_spec' => $this->text($l['spec']),
            'quantity' => (float) ($l['quantity'] ?? 0),
            'delivered_quantity' => (float) ($l['delivered_quantity'] ?? 0),
            'unit_raw' => $this->text($l['unit'], 20),
            'unit_price' => (float) ($l['unit_price'] ?? 0),
            'amount' => (float) ($l['amount'] ?? 0),
            'due_on' => $this->date($l['due_date'] ?? null),
            'customer_item_code' => $this->text($l['customer_item_code'], 60),
            'closed_code' => trim((string) ($l['closed_code'] ?? '')),
            'created_at' => $now, 'updated_at' => $now,
        ], $lines);

        if ($rows !== []) {
            OrderLine::upsert(
                $rows,
                ['order_id', 'seq'],
                ['item_code', 'erp_name', 'erp_spec', 'quantity', 'delivered_quantity', 'unit_raw', 'unit_price', 'amount',
                    'due_on', 'customer_item_code', 'closed_code', 'updated_at'],
            );
        }
        // Dòng không còn trong ERP (hoặc đã hủy) thì bỏ khỏi bản sao.
        OrderLine::where('order_id', $orderId)->whereNotIn('seq', array_column($lines, 'seq'))->delete();
    }

    /** 'YYYYMMDD' có thật => 'Y-m-d'; trống, '00000000', ngày không tồn tại => null. */
    private function date(mixed $value): ?string
    {
        if (! preg_match('/^(\d{4})(\d{2})(\d{2})$/', trim((string) $value), $m)
            || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }

    private function text(mixed $value, int $max = 255): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
