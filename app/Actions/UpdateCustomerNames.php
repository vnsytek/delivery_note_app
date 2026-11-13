<?php

namespace App\Actions;

use App\Models\Customer;
use App\Services\AccentDictionary;

/**
 * Lưu tên đúng dấu do người dùng sửa. Chuỗi rỗng/null = xóa bản sửa của trường đó.
 * Từ điển sửa dấu học theo: từ lỗi trong tên ERP được sửa giống vậy ở các khách hàng / phiếu khác.
 */
class UpdateCustomerNames
{
    public function __construct(private readonly AccentDictionary $dictionary) {}

    /** @param array{MA002?: ?string, MA003?: ?string} $names chỉ các trường có mặt mới bị thay đổi */
    public function __invoke(Customer $customer, array $names): Customer
    {
        foreach (['MA002' => 'ma002_manual', 'MA003' => 'ma003_manual'] as $field => $column) {
            if (! array_key_exists($field, $names)) {
                continue;
            }
            $value = trim((string) $names[$field]);
            $customer->{$column} = $value === '' ? null : $value;
            if ($value !== '') {
                $this->dictionary->learn($customer->{strtolower($field)}, $value);
            }
        }
        $customer->save();

        return $customer;
    }
}
