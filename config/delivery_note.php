<?php

return [
    // Thông tin công ty in ở đầu phiếu mẫu ERP (theo ảnh phiếu in). Sửa tại đây nếu đổi thông tin.
    'company' => [
        'name_zh' => env('COMPANY_NAME_ZH', '上逸科技服務有限公司'),
        'name_vi' => env('COMPANY_NAME_VI', 'CÔNG TY TNHH DỊCH VỤ VÀ KỸ THUẬT SANG Y'),
        'address' => env('COMPANY_ADDRESS', '303 Lê Duẩn, Kiến An, Hải Phòng'),
    ],

    // Ba mẫu in khổ A5 ngang:
    //  - excel:       mẫu trong file "phieu giao hang hdv.xlsx" (logo + tiêu đề 出貨單, cột Tên hàng / Quy cách / 客單號 / 备注)
    //  - erp-general: mẫu ERP CHUNG cho mọi khách (khối tên công ty, cột Quy cách / Số đơn khách / Mã hàng khách / Ghi chú, chân 客户签收 | 倉庫 | 填表)
    //  - erp:         mẫu ERP RIÊNG của khách có cột riêng, dựng theo ảnh phiếu UDE (PO No. / Mã hàng khách / Mã bộ phận)
    // lines_per_page = số dòng hàng tối đa trên một tờ; nhiều hơn thì tự sang tờ sau.
    // css = tên file trong public/css dùng cho mẫu (mặc định "print-<tên mẫu>").
    //
    // layout = số liệu để chia tờ theo chiều cao ước tính (PrintPaginator): budget_mm = chiều cao còn lại cho các dòng hàng,
    // line_mm / pad_mm / min_row_mm = chiều cao một hàng chữ / lề trên-dưới ô / dòng thấp nhất, latin_mm / han_mm = bề rộng
    // trung bình một chữ Latin / một chữ Hán, cells = các ô có thể làm cao dòng (bề rộng mm + trường dữ liệu trong ô).
    'templates' => [
        'excel' => [
            'lines_per_page' => 5,
            'layout' => [
                'budget_mm' => 66, 'line_mm' => 4.6, 'pad_mm' => 1.0, 'min_row_mm' => 4.76, 'latin_mm' => 1.9, 'han_mm' => 3.7,
                'cells' => [
                    ['width' => 24, 'fields' => ['item_code']],
                    ['width' => 40, 'fields' => ['name', 'name_zh']],
                    ['width' => 34, 'fields' => ['spec', 'spec_zh']],
                    ['width' => 27, 'fields' => ['customer_po']],
                    ['width' => 24, 'fields' => ['customer_item_code']],
                    ['width' => 13, 'fields' => ['remark']],
                ],
            ],
        ],
        'erp-general' => [
            'lines_per_page' => 5,
            'css' => 'print-erp',
            'layout' => [
                'budget_mm' => 56, 'line_mm' => 3.3, 'pad_mm' => 0.8, 'min_row_mm' => 7.5, 'latin_mm' => 1.55, 'han_mm' => 2.95, // 56 = 66 trừ phần địa chỉ + ô ký cao thêm (tổng cao ≤ 148 mm)
                'cells' => [
                    ['width' => 22, 'fields' => ['item_code']],
                    ['width' => 38, 'fields' => ['name', 'name_zh']],
                    ['width' => 28, 'fields' => ['spec', 'spec_zh']],
                    ['width' => 25, 'fields' => ['customer_po']],
                    ['width' => 25, 'fields' => ['customer_item_code']],
                    ['width' => 21, 'fields' => ['remark']],
                ],
            ],
        ],
        'erp' => [
            'lines_per_page' => 5,
            'layout' => [
                'budget_mm' => 66, 'line_mm' => 3.3, 'pad_mm' => 0.8, 'min_row_mm' => 7.5, 'latin_mm' => 1.55, 'han_mm' => 2.95,
                'cells' => [
                    ['width' => 28, 'fields' => ['item_code']],
                    ['width' => 39, 'fields' => ['name', 'name_zh']],
                    ['width' => 27, 'fields' => ['spec', 'spec_zh']],
                    ['width' => 24, 'fields' => ['customer_po']],
                    ['width' => 24, 'fields' => ['customer_item_code']],
                ],
            ],
        ],
    ],

    // Mẫu dùng cho khách chưa khai báo bên dưới.
    'default_template' => 'excel',

    // Mã khách (MA001) dùng mẫu riêng.
    'customer_templates' => [
        'UDE' => 'erp',
    ],

    // Tên hiển thị của các mẫu in trên giao diện.
    'template_labels' => [
        'excel' => 'Mẫu Excel',
        'erp-general' => 'Mẫu ERP chung',
        'erp' => 'Mẫu ERP riêng',
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
