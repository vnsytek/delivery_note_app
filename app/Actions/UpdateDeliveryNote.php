<?php

namespace App\Actions;

use App\Models\DeliveryNote;
use App\Models\ProductName;
use App\Services\AccentDictionary;
use App\Support\ChineseText;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Support\Facades\DB;

/**
 * Lưu phần nhập tay của phiếu. Tên hàng sửa trên phiếu được lưu vào danh mục theo mã hàng
 * để các phiếu sau dùng lại; để trống = quay về tên gợi ý tự động.
 */
class UpdateDeliveryNote
{
    public function __construct(
        private readonly VietnameseAccentRestorer $restorer,
        private readonly AccentDictionary $dictionary,
    ) {}

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
                $this->saveProductName($line->item_code, $line->erp_name, $this->clean($input['name'] ?? null), $this->clean($input['name_zh'] ?? null));
            }
        });
    }

    /**
     * Tên Việt / tên Trung giống gợi ý tự động thì không lưu; cả hai đều giống (hoặc để trống) thì xóa mục danh mục.
     */
    private function saveProductName(string $itemCode, ?string $erpName, ?string $name, ?string $nameZh): void
    {
        ['latin' => $latin, 'zh' => $autoZh] = ChineseText::split($erpName);
        $suggestion = $this->restorer->suggest($latin);

        // Chỉ lưu phần khác gợi ý tự động; phần nào trùng gợi ý thì để trống (= dùng gợi ý).
        $manualName = ($name !== null && $name !== $suggestion) ? $name : null;
        $manualZh = ($nameZh !== null && $nameZh !== $autoZh) ? $nameZh : null;

        if ($manualName === null && $manualZh === null) {
            ProductName::where('item_code', $itemCode)->delete();

            return;
        }
        ProductName::updateOrCreate(['item_code' => $itemCode], ['name' => $manualName, 'name_zh' => $manualZh]);

        if ($manualName !== null) {
            // Từ điển sửa dấu học theo cho các mặt hàng khác; chỉ phần Latin (đã tách chữ Hán) mới so được từng từ.
            $this->dictionary->learn($latin, $manualName);
        }
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
