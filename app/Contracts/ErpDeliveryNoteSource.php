<?php

namespace App\Contracts;

interface ErpDeliveryNoteSource
{
    /**
     * Đọc phiếu bán hàng (kèm dòng hàng) từ ERP, theo từng lô, từ ngày $since (YYYYMMDD).
     *
     * Mỗi phiếu là mảng:
     *  type, no, date (YYYYMMDD), customer_code, customer_name, address,
     *  lines: list<{seq, item_code, name, spec, quantity, unit, customer_po, customer_item_code, order_type, order_no, order_seq}>
     *
     * @return iterable<int, list<array<string, mixed>>>
     */
    public function chunks(string $since, int $size): iterable;
}
