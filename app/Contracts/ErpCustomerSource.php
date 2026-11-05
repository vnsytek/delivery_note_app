<?php

namespace App\Contracts;

interface ErpCustomerSource
{
    /**
     * Đọc khách hàng từ ERP theo từng lô. Mỗi phần tử là một dòng COPMA dạng [tên cột => giá trị].
     *
     * @return iterable<int, list<array<string, mixed>>>
     */
    public function chunks(int $size): iterable;
}
