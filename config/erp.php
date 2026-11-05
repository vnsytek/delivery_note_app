<?php

return [
    'delivery_note' => [
        // Loại phiếu bán hàng (COPTG.TG001) cần đồng bộ.
        'types' => ['V231', 'V233', 'V235'],

        // Chỉ đồng bộ phiếu từ ngày này (YYYYMMDD) trở đi; ghi đè bằng --since.
        'since' => env('ERP_DELIVERY_SINCE', '20260101'),

        // Tên cột ERP (Digiwin/COP). TG001-TG004, TH001-TH006, TH014-TH016, TD014, TC012 đã thấy trong
        // các truy vấn của file COPn; các cột dưới đây CHƯA kiểm chứng trên SY2019A - xem README
        // ("Kiểm tra cột ERP") và sửa tại đây nếu tên cột khác.
        'columns' => [
            'header_customer_name' => 'TG007',
            'header_address' => 'TG008',
            'line_quantity' => 'TH008',
            'line_unit' => 'TH009',
        ],
    ],
];
