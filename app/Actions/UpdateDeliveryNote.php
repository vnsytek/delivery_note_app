<?php

namespace App\Actions;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\ProductName;
use App\Services\AccentDictionary;
use App\Services\ProductLineText;
use App\Support\ChineseText;
use Illuminate\Support\Facades\DB;

/**
 * Lưu phần nhập tay của phiếu. Tên hàng sửa trên phiếu được lưu vào danh mục theo mã hàng
 * để các phiếu sau dùng lại; để trống = quay về tên gợi ý tự động.
 */
class UpdateDeliveryNote
{
    public function __construct(
        private readonly ProductLineText $text,
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
                $this->saveProductText($line, [
                    'name' => $this->clean($input['name'] ?? null),
                    'name_zh' => $this->clean($input['name_zh'] ?? null),
                    'spec' => $this->clean($input['spec'] ?? null),
                    'spec_zh' => $this->clean($input['spec_zh'] ?? null),
                ]);
            }
        });
    }

    /**
     * Lưu tên hàng / quy cách (Việt, Trung) theo mã hàng. Giá trị trùng bản tự động thì không lưu (= tiếp tục dùng bản tự động);
     * cả bốn đều trùng hoặc để trống thì xóa mục danh mục của mã hàng.
     *
     * @param  array{name: ?string, name_zh: ?string, spec: ?string, spec_zh: ?string}  $input
     */
    private function saveProductText(DeliveryNoteLine $line, array $input): void
    {
        $auto = $this->text->build($line->item_code, $line->erp_name, $line->erp_spec);

        $manual = [
            'name' => $this->differs($input['name'], $auto['name_suggested']),
            'name_zh' => $this->differs($input['name_zh'], $auto['name_zh_auto']),
            'spec' => $this->differs($input['spec'], $auto['spec_suggested']),
            'spec_zh' => $this->differs($input['spec_zh'], $auto['spec_zh_auto']),
        ];

        if (array_filter($manual, fn ($value) => $value !== null) === []) {
            ProductName::where('item_code', $line->item_code)->delete();

            return;
        }
        ProductName::updateOrCreate(['item_code' => $line->item_code], $manual);

        if ($manual['name'] !== null) {
            // Từ điển sửa dấu học theo cho các mặt hàng khác; chỉ phần Latin (đã tách chữ Hán) mới so được từng từ.
            $this->dictionary->learn(ChineseText::split($line->erp_name)['latin'], $manual['name']);
        }
    }

    /** Giá trị người nhập nếu khác bản tự động, ngược lại null. */
    private function differs(?string $submitted, ?string $auto): ?string
    {
        return ($submitted !== null && $submitted !== (string) $auto) ? $submitted : null;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
