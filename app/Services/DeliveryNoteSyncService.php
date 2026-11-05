<?php

namespace App\Services;

use App\Contracts\ErpDeliveryNoteSource;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ phiếu bán hàng ERP -> MySQL (upsert theo số phiếu). Không xóa phiếu, không ghi đè
 * các cột nhập tay (receiver, supplier_code, address_manual, note, dept_code, remark).
 */
class DeliveryNoteSyncService
{
    private const CHUNK_SIZE = 200;

    public function __construct(private readonly ErpDeliveryNoteSource $source) {}

    /**
     * @return array{synced: int, invalid_number: int, unknown_customer: int}
     */
    public function sync(string $since): array
    {
        $result = ['synced' => 0, 'invalid_number' => 0, 'unknown_customer' => 0];

        foreach ($this->source->chunks($since, self::CHUNK_SIZE) as $chunk) {
            $knownCustomers = Customer::whereIn('ma001', array_column($chunk, 'customer_code'))->pluck('ma001')->all();
            $valid = [];
            foreach ($chunk as $note) {
                if (! $this->hasValidNumber($note)) {
                    $result['invalid_number']++;
                } elseif (! in_array($note['customer_code'], $knownCustomers, true)) {
                    $result['unknown_customer']++;
                } else {
                    $valid[] = $note;
                }
            }

            // Mỗi lô là một transaction: lỗi giữa chừng không để lại lô ghi dở.
            DB::transaction(fn () => $this->store($valid));
            $result['synced'] += count($valid);
        }

        return $result;
    }

    /** Số phiếu hợp lệ: loại 4 ký tự, số 9 chữ số (yymmdd + 3 số thứ tự), ngày YYYYMMDD. */
    private function hasValidNumber(array $note): bool
    {
        return preg_match('/^[A-Z0-9]{4}$/', $note['type']) === 1
            && preg_match('/^\d{9}$/', $note['no']) === 1
            && preg_match('/^\d{8}$/', $note['date']) === 1;
    }

    private function store(array $notes): void
    {
        if ($notes === []) {
            return;
        }
        $now = now();

        DeliveryNote::upsert(
            array_map(fn (array $n) => [
                'erp_type' => $n['type'],
                'erp_no' => $n['no'],
                'issued_on' => substr($n['date'], 0, 4).'-'.substr($n['date'], 4, 2).'-'.substr($n['date'], 6, 2),
                'customer_code' => $n['customer_code'],
                'erp_customer_name' => $this->text($n['customer_name']),
                'erp_address' => $this->text($n['address']),
                'synced_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ], $notes),
            ['erp_type', 'erp_no'],
            ['issued_on', 'customer_code', 'erp_customer_name', 'erp_address', 'synced_at', 'updated_at'],
        );

        foreach ($notes as $n) {
            $id = DeliveryNote::where('erp_type', $n['type'])->where('erp_no', $n['no'])->value('id');
            $this->storeLines($id, $n['lines'], $now);
        }
    }

    private function storeLines(int $noteId, array $lines, $now): void
    {
        $rows = array_map(fn (array $l) => [
            'delivery_note_id' => $noteId,
            'seq' => $l['seq'],
            'item_code' => $l['item_code'],
            'erp_name' => $this->text($l['name']),
            'erp_spec' => $this->text($l['spec']),
            'quantity' => $l['quantity'] ?? 0,
            'unit_raw' => $this->text($l['unit']),
            'customer_po' => $this->text($l['customer_po']),
            'customer_item_code' => $this->text($l['customer_item_code']),
            'created_at' => $now, 'updated_at' => $now,
        ], $lines);

        if ($rows !== []) {
            DeliveryNoteLine::upsert(
                $rows,
                ['delivery_note_id', 'seq'],
                ['item_code', 'erp_name', 'erp_spec', 'quantity', 'unit_raw', 'customer_po', 'customer_item_code', 'updated_at'],
            );
        }
        // Dòng không còn trong ERP thì bỏ khỏi bản sao.
        DeliveryNoteLine::where('delivery_note_id', $noteId)->whereNotIn('seq', array_column($lines, 'seq'))->delete();
    }

    private function text(mixed $value): ?string
    {
        $value = $value === null ? '' : trim((string) $value);

        return $value === '' ? null : $value;
    }
}
