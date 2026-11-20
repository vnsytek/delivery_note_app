<?php

/*
 * Từ điển Việt ⇄ Trung (phồn thể) cho phần QUY CÁCH / mô tả ngắn của hàng hóa, dùng để bảo đảm mỗi dòng hàng có
 * đủ hai thứ tiếng trên phiếu in và đơn đặt hàng (BilingualGlossary).
 *
 *  - 'terms': tiếng Việt (viết thường, KHÔNG dấu) => tiếng Trung. Cụm dài khớp trước cụm ngắn. Dùng cho chiều Việt -> Trung.
 *  - 'reverse': cụm chữ Hán (cả giản thể lẫn phồn thể) => tiếng Việt. Dùng cho chiều Trung -> Việt khi ERP chỉ ghi chữ Hán.
 *
 * Mã hàng, số đo, model (L3110, 80x50, SSD...) luôn giữ nguyên, không dịch. Chỉ dịch được những từ có trong bảng
 * này; từ nào chưa có thì giữ nguyên, nên thêm vào đây khi gặp từ mới.
 */
return [
    'terms' => [
        // Chỉ những từ ít nhập nhằng. Từ ngắn dễ trùng với tiếng Anh/mã (to, do, bo, cam, day, cap, nam...) cố ý KHÔNG đưa vào.
        // màu sắc
        'xanh duong' => '藍色', 'xanh la' => '綠色', 'xanh' => '藍色', 'vang' => '黃色', 'den' => '黑色', 'trang' => '白色',
        'xam' => '灰色', 'hong' => '粉紅色', 'tim' => '紫色',
        // đơn vị, bao gói
        'cuon' => '卷', 'cai' => '個', 'chiec' => '個', 'hop' => '盒', 'binh' => '瓶', 'soi' => '條', 'goi' => '包',
        'tem' => '標籤', 'thung' => '箱', 'pcs' => '個', 'tui' => '袋',
        // mô tả thường gặp
        // danh từ + tính từ: tiếng Trung đảo thứ tự (chuột không dây = 無線滑鼠)
        'chuot khong day' => '無線滑鼠', 'chuot co day' => '有線滑鼠', 'ban phim khong day' => '無線鍵盤', 'ban phim co day' => '有線鍵盤',
        'khong day' => '無線', 'co day' => '有線', 'kem' => '附', 'man hinh' => '螢幕', 'ban phim' => '鍵盤', 'chuot' => '滑鼠',
        'may in' => '印表機', 'muc in' => '墨水', 'muc' => '墨水', 'giay' => '紙', 'ruy bang' => '色帶', 'bang muc' => '色帶',
        'ma vach' => '條碼', 'chinh hang' => '原廠', 'bao hanh' => '保固', 'tan nhiet' => '散熱', 'pin' => '電池',
    ],

    'reverse' => [
        '條碼色帶' => 'băng mực mã vạch', '条码色带' => 'băng mực mã vạch', '色帶' => 'băng mực', '色带' => 'băng mực',
        '打印機油墨' => 'mực in', '打印机油墨' => 'mực in', '墨水' => 'mực',
        '含屏幕' => 'kèm màn hình', '含螢幕' => 'kèm màn hình', '屏幕' => 'màn hình', '螢幕' => 'màn hình', '顯示器' => 'màn hình',
        '无线' => 'không dây', '無線' => 'không dây', '有線' => 'có dây', '电脑' => 'máy tính', '電腦' => 'máy tính',
        '光纤跳线' => 'dây nhảy quang', '光纖跳線' => 'dây nhảy quang', '枪型' => 'dạng súng', '槍型' => 'dạng súng', '室外' => 'ngoài trời',
        '乾燥組件' => 'bộ sấy', '含安裝' => 'kèm lắp đặt', '含安装' => 'kèm lắp đặt', '监控安装' => 'lắp đặt giám sát', '監控安裝' => 'lắp đặt giám sát',
        '罗技鼠标' => 'chuột Logitech', '羅技滑鼠' => 'chuột Logitech', '水洗碳带' => 'ruy băng carbon', '水洗碳帶' => 'ruy băng carbon',
        '会议麦克风' => 'micro hội nghị', '會議麥克風' => 'micro hội nghị', '网路线' => 'cáp mạng', '網路線' => 'cáp mạng',
        '视讯会议' => 'họp video', '視訊會議' => 'họp video', '新品故障送修' => 'hàng mới lỗi gửi sửa',
        '修理' => 'sửa chữa', '條碼掃描器' => 'máy quét mã vạch', '条码扫描器' => 'máy quét mã vạch', '顯示屏' => 'màn hình', '显示屏' => 'màn hình',
        '主機板' => 'bo mạch chủ', '主机板' => 'bo mạch chủ', '不斷電系統' => 'bộ lưu điện (UPS)', '不断电系统' => 'bộ lưu điện (UPS)', '彩色' => 'màu',
        '黄' => 'vàng', '黃' => 'vàng', '蓝' => 'xanh', '藍' => 'xanh', '红' => 'đỏ', '紅' => 'đỏ', '黑' => 'đen', '白' => 'trắng',
        '灰' => 'xám', '寸' => 'inch', '卷' => 'cuộn', '個' => 'cái', '个' => 'cái', '套' => 'bộ', '盒' => 'hộp',
    ],
];
