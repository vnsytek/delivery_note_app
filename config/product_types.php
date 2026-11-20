<?php

/*
 * Phân loại hàng hóa để ghi "đây là thiết bị gì" (song ngữ Việt - Trung) trên đơn đặt hàng.
 *
 * Cách nhận biết (ProductTypeClassifier):
 *  1. Tiền tố của MÃ HÀNG (codes): mã trong ERP đặt theo loại - NB* là laptop, MR* là màn hình, SSD*, CAM*... Khớp tiền tố dài nhất.
 *  2. Nếu mã không rõ (tiền tố là tên hãng như UG, LOG), tìm từ khóa trong TÊN hàng (words), theo thứ tự khai báo
 *     dưới đây - loại cụ thể phải đứng trước loại chung. Từ khóa viết thường, KHÔNG dấu.
 *  3. Không khớp gì thì không ghi loại.
 *
 * vi = tên loại tiếng Việt; zh = tên loại tiếng Trung (phồn thể). Thêm/sửa loại ở đây, không cần sửa code.
 */
return [
    'barcode_printer' => ['vi' => 'Máy in mã vạch, đầu in', 'zh' => '條碼印表機／列印頭', 'codes' => ['DAUZT'], 'words' => ['may in ma vach', 'may in tem', 'dau in ma vach']],
    'label_paper' => ['vi' => 'Giấy in tem, giấy in nhiệt', 'zh' => '標籤紙／熱感紙', 'codes' => ['GIT'], 'words' => ['giay in tem', 'giay in nhiet', 'tem ma vach', 'tem nhan', 'giay in']],
    'ink' => ['vi' => 'Mực in, ruy băng, drum', 'zh' => '墨水／碳粉／色帶', 'codes' => ['MUC'], 'words' => ['muc in', 'ruy bang', 'toner', 'hop muc', 'lo muc', 'muc do', 'drum']],
    'printer' => ['vi' => 'Máy in', 'zh' => '印表機', 'codes' => ['PRT'], 'words' => ['may in', 'printer']],
    'laptop' => ['vi' => 'Máy tính xách tay (laptop)', 'zh' => '筆記型電腦', 'codes' => ['NB'], 'words' => ['laptop', 'may tinh xach tay', 'notebook', 'macbook', 'thinkpad', 'expertbook']],
    'desktop' => ['vi' => 'Máy tính để bàn', 'zh' => '桌上型電腦', 'codes' => ['PC', 'WSE', 'NUC', 'IPC'], 'words' => ['may tinh de ban', 'desktop', 'workstation', 'mini pc', 'pc']],
    'server' => ['vi' => 'Máy chủ (server)', 'zh' => '伺服器', 'codes' => ['SVR'], 'words' => ['server', 'may chu']],
    'tablet' => ['vi' => 'Máy tính bảng', 'zh' => '平板電腦', 'codes' => ['PAD'], 'words' => ['may tinh bang', 'tablet', 'ipad']],
    'monitor' => ['vi' => 'Màn hình', 'zh' => '顯示器', 'codes' => ['MR', 'MH'], 'words' => ['man hinh', 'monitor', 'lcd']],
    'tv' => ['vi' => 'Tivi', 'zh' => '電視機', 'codes' => ['TV'], 'words' => ['tivi', 'tv']],
    'projector' => ['vi' => 'Máy chiếu', 'zh' => '投影機', 'codes' => [], 'words' => ['may chieu', 'projector']],
    'camera' => ['vi' => 'Camera giám sát', 'zh' => '監視攝影機', 'codes' => ['CAM'], 'words' => ['camera', 'webcam']],
    'nvr' => ['vi' => 'Đầu ghi hình (NVR/DVR)', 'zh' => '錄影主機', 'codes' => ['NVR', 'DVR'], 'words' => ['dau ghi hinh', 'nvr', 'dvr']],
    'access' => ['vi' => 'Thiết bị chấm công, kiểm soát ra vào', 'zh' => '考勤門禁設備', 'codes' => ['MCC', 'THETU'], 'words' => ['cham cong', 'kiem soat', 'van tay', 'the tu']],
    'nfc' => ['vi' => 'Đầu đọc, thẻ NFC', 'zh' => 'NFC讀卡機／標籤', 'codes' => ['NFC'], 'words' => ['nfc']],
    'radio' => ['vi' => 'Bộ đàm', 'zh' => '對講機', 'codes' => ['BODAM', 'MBD'], 'words' => ['bo dam']],
    'lock' => ['vi' => 'Khóa điện tử', 'zh' => '電子鎖', 'codes' => ['KHO'], 'words' => ['khoa hut tu', 'khoa']],
    'storage_media' => ['vi' => 'Thẻ nhớ, USB', 'zh' => '記憶卡／隨身碟', 'codes' => ['MIC', 'SDM', 'UDD', 'UD', 'SD'], 'words' => ['the nho', 'micro sd', 'microsd', 'usb flash', 'sandisk']],
    'audio' => ['vi' => 'Âm thanh (loa, tai nghe, micro)', 'zh' => '音響／耳機／麥克風', 'codes' => ['LOA'], 'words' => ['loa', 'tai nghe', 'headset', 'earpods', 'earphone', 'headphone', 'micro', 'mixer', 'amplifier', 'speaker']],
    'switch' => ['vi' => 'Switch (chuyển mạch)', 'zh' => '網路交換器', 'codes' => ['SW'], 'words' => ['switch', 'chuyen mach']],
    'wifi' => ['vi' => 'WiFi, router', 'zh' => '無線網路設備', 'codes' => ['WIF'], 'words' => ['wifi', 'router', 'access point']],
    'nic' => ['vi' => 'Card mạng', 'zh' => '網路卡', 'codes' => ['NIC'], 'words' => ['card mang', 'ethernet adapter']],
    'network_cable' => ['vi' => 'Cáp mạng', 'zh' => '網路線', 'codes' => ['CAT', 'C5'], 'words' => ['cap mang', 'cat6', 'cat5', 'utp']],
    'fiber' => ['vi' => 'Cáp quang, bộ chuyển đổi quang', 'zh' => '光纖／光電轉換器', 'codes' => ['FB'], 'words' => ['cap quang', 'dau quang', 'chuyen doi quang', 'fiber']],
    'adapter' => ['vi' => 'Bộ chuyển đổi, hub USB', 'zh' => '轉接器／集線器', 'codes' => ['USBRS'], 'words' => ['bo chuyen', 'bo chia', 'hub usb', 'usb hub', 'typec', 'type c']],
    'cable' => ['vi' => 'Cáp, dây nối', 'zh' => '線材', 'codes' => ['CAP'], 'words' => ['cap', 'day nguon', 'hdmi', 'adapter', 'dau chuyen', 'chuyen doi']],
    'ups' => ['vi' => 'Bộ lưu điện (UPS)', 'zh' => '不斷電系統', 'codes' => ['UPS'], 'words' => ['ups', 'luu dien']],
    'ssd' => ['vi' => 'Ổ cứng SSD', 'zh' => '固態硬碟', 'codes' => ['SSD'], 'words' => ['ssd']],
    'hdd' => ['vi' => 'Ổ cứng HDD', 'zh' => '硬碟', 'codes' => ['HDD'], 'words' => ['hdd', 'o cung']],
    'ram' => ['vi' => 'RAM (bộ nhớ trong)', 'zh' => '記憶體', 'codes' => ['RAM'], 'words' => ['ram']],
    'cpu' => ['vi' => 'CPU (vi xử lý)', 'zh' => '處理器', 'codes' => ['CPU'], 'words' => ['cpu', 'chip vi xu ly', 'vi xu ly', 'processor']],
    'vga' => ['vi' => 'Card đồ họa (VGA)', 'zh' => '顯示卡', 'codes' => ['VGA'], 'words' => ['card vga', 'card do hoa']],
    'mainboard' => ['vi' => 'Bo mạch chủ (mainboard)', 'zh' => '主機板', 'codes' => ['MBH', 'MBB'], 'words' => ['mainboard', 'bo mach chu']],
    'psu' => ['vi' => 'Bộ nguồn máy tính', 'zh' => '電源供應器', 'codes' => ['PWC', 'PW'], 'words' => ['bo nguon', 'nguon may tinh', 'psu']],
    'fan' => ['vi' => 'Quạt tản nhiệt', 'zh' => '散熱風扇', 'codes' => ['FAN'], 'words' => ['tan nhiet', 'quat']],
    'case' => ['vi' => 'Vỏ máy tính', 'zh' => '機殼', 'codes' => ['VCE'], 'words' => ['vo may tinh', 'case']],
    'nas' => ['vi' => 'Thiết bị lưu trữ NAS', 'zh' => '網路儲存設備', 'codes' => ['NAS'], 'words' => ['nas']],
    'mouse' => ['vi' => 'Chuột máy tính', 'zh' => '滑鼠', 'codes' => [], 'words' => ['chuot', 'mouse']],
    'keyboard' => ['vi' => 'Bàn phím', 'zh' => '鍵盤', 'codes' => [], 'words' => ['ban phim', 'keyboard']],
    'scanner' => ['vi' => 'Máy quét mã vạch', 'zh' => '條碼掃描器', 'codes' => ['SCN', 'SCD'], 'words' => ['may doc ma vach', 'dau doc ma vach', 'may quet', 'scanner']],
    'embedded' => ['vi' => 'Mạch nhúng (Raspberry Pi)', 'zh' => '嵌入式開發板', 'codes' => ['RBP'], 'words' => ['raspberry']],
    'battery' => ['vi' => 'Ắc quy, pin', 'zh' => '電池', 'codes' => ['ACQUY'], 'words' => ['ac quy', 'pin']],
    'paper' => ['vi' => 'Giấy in liên tục, giấy văn phòng', 'zh' => '連續報表紙／辦公用紙', 'codes' => ['GIAY'], 'words' => ['giay']],
    'phone' => ['vi' => 'Điện thoại IP', 'zh' => 'IP電話機', 'codes' => ['TEL'], 'words' => ['ip phone', 'dien thoai']],
    'charger' => ['vi' => 'Sạc, adapter nguồn', 'zh' => '充電器', 'codes' => ['SAC'], 'words' => ['sac', 'charger']],
    'software' => ['vi' => 'Phần mềm, bản quyền', 'zh' => '軟體／授權', 'codes' => ['SFW', 'SNF'], 'words' => ['phan mem', 'ban quyen', 'licence', 'license']],
    'service' => ['vi' => 'Dịch vụ, sửa chữa', 'zh' => '維修服務', 'codes' => ['SUA', 'PHI', 'CT'], 'words' => ['dich vu', 'sua chua', 'phi cong', 'lap dat']],
    'rack' => ['vi' => 'Tủ rack, tủ điện', 'zh' => '機櫃／電箱', 'codes' => ['TUR', 'TUDIEN'], 'words' => ['tu rack', 'rack', 'tu dien']],
    'hardware' => ['vi' => 'Vật tư, phụ kiện lắp đặt', 'zh' => '五金／安裝配件', 'codes' => ['PVC', 'H-', 'VATTU', 'ONGSUN'], 'words' => ['khop noi', 'oc vit', 'gia do', 'vat tu', 'ong sun']],
];
