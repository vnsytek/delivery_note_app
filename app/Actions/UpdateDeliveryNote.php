<?php

namespace App\Actions;

use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;

/**
 * Lưu phần nhập tay của phiếu. Tên hàng sửa trên phiếu được lưu vào danh mục theo mã hàng
 * để các phiếu sau dùng lại; để trống = quay về tên gợi ý tự động.
 */
class UpdateDeliveryNote
{
    public function __construct(private readonly SaveProductName $saveProductName) {}

    /**
     * @param  array{receiver?: ?string, supplier_code?: ?string, address_manual?: ?string, note?: ?string, lines?: array<int, array<string, ?string>>}  $data
     */
    public function __invoke(DeliveryNote $note, array $data): void
    {
        // Phiếu + dòng + danh mục tên hàng phải cùng thành công hoặc cùng thất bại.
        DB::transaction(function () use ($note, $data) {
            $note->update([
                'receiver' => $this->clean($data['receiver'] ?? null),
                'supplier_code' => $this->clean($data['supplier_code'] ?? null),
                'address_manual' => $this->clean($data['address_manual'] ?? null),
                'note' => $this->clean($data['note'] ?? null),
            ]);

            // Chỉ xử lý các dòng thật sự thuộc phiếu này, bỏ qua id lạ.
            foreach ($note->lines as $line) {
                $input = $data['lines'][$line->id] ?? null;
                if ($input === null) {
                    continue;
                }
                $changes = [
                    'dept_code' => $this->clean($input['dept_code'] ?? null),
                    'remark' => $this->clean($input['remark'] ?? null),
                ];
                if (array_key_exists('quantity', $input)) {
                    $changes['quantity_manual'] = $this->manualQuantity($input['quantity'], (float) $line->quantity);
                }
                $line->update($changes);
                // Tên Việt / tên Trung lưu theo mã hàng (dùng lại cho mọi phiếu); để trống = quay về gợi ý tự động.
                ($this->saveProductName)($line->item_code, $line->erp_name, $line->erp_spec, [
                    'name' => $input['name'] ?? null,
                    'name_zh' => $input['name_zh'] ?? null,
                ]);
            }
        });
    }

    /**
     * Số lượng sửa tay chỉ lưu khi khác số ERP; để trống hoặc nhập đúng số ERP = in theo ERP.
     * Chỉ ghi vào MySQL của app (cột quantity_manual), số lượng trong ERP không đổi.
     */
    private function manualQuantity(mixed $value, float $erpQuantity): ?float
    {
        $value = $this->clean(is_scalar($value) ? (string) $value : null);
        if ($value === null) {
            return null;
        }
        $quantity = round((float) str_replace(',', '.', $value), 3);

        return abs($quantity - $erpQuantity) < 0.0005 ? null : $quantity;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
