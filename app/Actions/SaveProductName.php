<?php

namespace App\Actions;

use App\Models\ProductName;
use App\Services\AccentDictionary;
use App\Services\ProductNameResolver;

/**
 * Lưu tên hàng sửa tay theo mã hàng (dùng chung cho trang phiếu giao và trang "Tên hàng").
 *
 * Chỉ các trường có trong $input mới bị đổi (name, name_zh, spec). Giá trị trống hoặc trùng gợi ý tự động
 * được lưu là null (= dùng gợi ý, sau này từ điển sửa dấu tốt hơn thì tên cũng tốt theo). Cả ba đều null thì xóa mục.
 * Chỉ ghi vào MySQL của app; ERP không bị đổi.
 */
class SaveProductName
{
    /** Trường lưu => khóa gợi ý tự động tương ứng trong ProductNameResolver::present(). */
    private const FIELDS = ['name' => 'name_suggested', 'name_zh' => 'name_zh_auto', 'spec' => 'spec_suggested'];

    public function __construct(
        private readonly ProductNameResolver $resolver,
        private readonly AccentDictionary $dictionary,
    ) {}

    /** @param  array{name?: ?string, name_zh?: ?string, spec?: ?string}  $input */
    public function __invoke(string $itemCode, ?string $erpName, ?string $erpSpec, array $input): ?ProductName
    {
        $auto = $this->resolver->present($erpName, $erpSpec, null);
        $entry = ProductName::firstOrNew(['item_code' => $itemCode]);

        foreach (self::FIELDS as $field => $autoKey) {
            if (! array_key_exists($field, $input)) {
                continue;
            }
            $value = $this->clean($input[$field]);
            $entry->{$field} = ($value === null || $value === $auto[$autoKey]) ? null : $value;
        }

        if ($entry->name === null && $entry->name_zh === null && $entry->spec === null) {
            if ($entry->exists) {
                $entry->delete();
            }

            return null;
        }
        $entry->save();

        if (array_key_exists('name', $input) && $entry->name !== null) {
            $this->dictionary->learn($auto['latin'], $entry->name); // từ điển sửa dấu học theo cho các mặt hàng khác
        }

        return $entry;
    }

    private function clean(mixed $value): ?string
    {
        $value = trim(is_scalar($value) ? (string) $value : '');

        return $value === '' ? null : $value;
    }
}
