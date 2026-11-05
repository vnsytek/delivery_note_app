<?php

namespace App\Actions;

use App\Models\DeliveryNote;
use App\Models\ProductName;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Support\Facades\DB;

/**
 * Lưu phần nhập tay của phiếu. Tên hàng sửa trên phiếu được lưu vào danh mục theo mã hàng
 * để các phiếu sau dùng lại; để trống = quay về tên gợi ý tự động.
 */
class UpdateDeliveryNote
{
    public function __construct(private readonly VietnameseAccentRestorer $restorer) {}

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
                $line->update([
                    'dept_code' => $this->clean($input['dept_code'] ?? null),
                    'remark' => $this->clean($input['remark'] ?? null),
                ]);
                $this->saveProductName($line->item_code, $line->erp_name, $this->clean($input['name'] ?? null));
            }
        });
    }

    private function saveProductName(string $itemCode, ?string $erpName, ?string $name): void
    {
        if ($name === null) {
            ProductName::where('item_code', $itemCode)->delete();

            return;
        }
        $hasEntry = ProductName::where('item_code', $itemCode)->exists();
        $suggestion = $this->restorer->suggest(trim((string) $erpName));
        if (! $hasEntry && $name === $suggestion) {
            return; // không đổi gì so với gợi ý thì không tạo mục danh mục
        }
        ProductName::updateOrCreate(['item_code' => $itemCode], ['name' => $name]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
