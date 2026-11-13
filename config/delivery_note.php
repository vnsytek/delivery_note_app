<?php

return [
    // Thông tin công ty in ở đầu phiếu mẫu ERP (theo ảnh phiếu in). Sửa tại đây nếu đổi thông tin.
    'company' => [
        'name_zh' => env('COMPANY_NAME_ZH', '上逸科技服務有限公司'),
        'name_vi' => env('COMPANY_NAME_VI', 'CÔNG TY TNHH DỊCH VỤ VÀ KỸ THUẬT SANG Y'),
        'address' => env('COMPANY_ADDRESS', '303 Lê Duẩn, Kiến An, Hải Phòng'),
    ],

    // Hai mẫu in khổ A5 ngang:
    //  - excel: mẫu trong file "phieu giao hang hdv.xlsx" (logo + tiêu đề 出貨單, cột Tên hàng / Quy cách / 客單號 / 备注)
    //  - erp:   mẫu phiếu in từ ERP trong ảnh (khối tên công ty, cột Quy cách / PO No. / Mã hàng khách / Mã bộ phận)
    // lines_per_page = số dòng hàng tối đa trên một tờ; nhiều hơn thì tự sang tờ sau.
    'templates' => [
        'excel' => ['lines_per_page' => 5],
        'erp' => ['lines_per_page' => 5],
    ],

    // Mẫu dùng cho khách chưa khai báo bên dưới.
    'default_template' => 'excel',

    // Mã khách (MA001) dùng mẫu riêng.
    'customer_templates' => [
        'UDE' => 'erp',
    ],

    // Tên hiển thị của hai mẫu in trên giao diện.
    'template_labels' => [
        'excel' => 'Mẫu Excel',
        'erp' => 'Mẫu ERP',
    ],

    // Tên loại phiếu (COPTG.TG001) hiển thị dưới số phiếu.
    'type_labels' => [
        'V231' => 'Phiếu giao hàng',
        'V233' => 'Phiếu giao sửa chữa',
        'V235' => 'Phiếu giao công trình',
    ],

    // Đơn đặt hàng (COPTC.TC001) đồng bộ để xem / chụp gửi khách; cặp với phiếu giao V231 / V233 / V235.
    'order_types' => ['V221', 'V223', 'V225'],
    'order_type_labels' => [
        'V221' => 'Đơn đặt hàng',
        'V223' => 'Đơn sửa chữa',
        'V225' => 'Đơn công trình',
    ],

    // Giờ hiển thị trên giao diện (dữ liệu lưu theo UTC).
    'timezone' => 'Asia/Ho_Chi_Minh',
];
