<?php

namespace App\Contracts;

interface ErpOrderSource
{
    /**
     * Đọc đơn đặt hàng (kèm dòng) từ ERP, theo từng lô, từ ngày $since (YYYYMMDD). CHỈ ĐỌC.
     *
     * Mỗi đơn là mảng:
     *  type, no, date (YYYYMMDD), customer_code, customer_po, tax_type, tax_rate, amount, tax, confirm_code,
     *  lines: list<{seq, item_code, name, spec, quantity, delivered_quantity, unit, unit_price, amount,
     *               due_date (YYYYMMDD), customer_item_code, closed_code}>
     *
     * @return iterable<int, list<array<string, mixed>>>
     */
    public function chunks(string $since, int $size): iterable;
}
