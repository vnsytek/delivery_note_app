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
                $changes = [
                    'dept_code' => $this->clean($input['dept_code'] ?? null),
                    'remark' => $this->clean($input['remark'] ?? null),
                ];
                if (array_key_exists('quantity', $input)) {
                    $changes['quantity_manual'] = $this->manualQuantity($input['quantity'], (float) $line->quantity);
                }
                $line->update($changes);
                // Chỉ các ô form có gửi mới được cập nhật (vd lưu phiếu không có ô quy cách thì giữ quy cách đã sửa).
                $this->saveProductText($line, array_map(
                    fn ($value) => $this->clean(is_scalar($value) ? (string) $value : null),
                    array_intersect_key($input, array_flip(['name', 'name_zh', 'spec', 'spec_zh'])),
                ));
            }
        });
    }

    /**
     * Lưu tên hàng / quy cách (Việt, Trung) theo mã hàng. Chỉ các trường có trong $input bị đổi. Giá trị trùng bản tự động
     * thì không lưu (= tiếp tục dùng bản tự động); cả bốn trường đều trống thì xóa mục danh mục của mã hàng.
     *
     * @param  array{name?: ?string, name_zh?: ?string, spec?: ?string, spec_zh?: ?string}  $input
     */
    private function saveProductText(DeliveryNoteLine $line, array $input): void
    {
        if ($input === []) {
            return;
        }
        $auto = $this->text->build($line->item_code, $line->erp_name, $line->erp_spec);
        $autoKeys = ['name' => 'name_suggested', 'name_zh' => 'name_zh_auto', 'spec' => 'spec_suggested', 'spec_zh' => 'spec_zh_auto'];

        $entry = ProductName::firstOrNew(['item_code' => $line->item_code]);
        foreach ($input as $field => $value) {
            $entry->{$field} = $this->differs($value, $auto[$autoKeys[$field]]);
        }

        if ($entry->name === null && $entry->name_zh === null && $entry->spec === null && $entry->spec_zh === null) {
            if ($entry->exists) {
                $entry->delete();
            }

            return;
        }
        $nameChanged = $entry->isDirty('name');
        $entry->save();

        if ($nameChanged && $entry->name !== null) {
            // Từ điển sửa dấu học theo cho các mặt hàng khác; chỉ phần Latin (đã tách chữ Hán) mới so được từng từ.
            $this->dictionary->learn(ChineseText::split($line->erp_name)['latin'], $entry->name);
        }
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
