<?php

namespace App\Actions;

use App\Models\Customer;

/** Lưu tên đúng dấu do người dùng sửa. Chuỗi rỗng/null = xóa bản sửa của trường đó. */
class UpdateCustomerNames
{
    /** @param array{MA002?: ?string, MA003?: ?string} $names chỉ các trường có mặt mới bị thay đổi */
    public function __invoke(Customer $customer, array $names): Customer
    {
        foreach (['MA002' => 'ma002_manual', 'MA003' => 'ma003_manual'] as $field => $column) {
            if (! array_key_exists($field, $names)) {
                continue;
            }
            $value = trim((string) $names[$field]);
            $customer->{$column} = $value === '' ? null : $value;
        }
        $customer->save();

        return $customer;
    }
}
