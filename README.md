# ERP Khách hàng (Laravel + MySQL)

Bản chuyển từ ứng dụng Python/FastAPI (`../webapp`) sang **Laravel 12 + MySQL (XAMPP)**.
Xem danh sách khách hàng ERP (bảng `COPMA`), tự khôi phục gợi ý dấu tiếng Việt bị mất (`?`), cho phép sửa tay tên đúng dấu.
**Không bao giờ ghi ngược vào ERP**: dữ liệu ERP được sao chép vào MySQL; bản sửa tay nằm riêng trong MySQL.

## Yêu cầu

| Thành phần | Phiên bản |
|---|---|
| PHP | **≥ 8.2** (extension: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`) |
| MySQL / MariaDB | MySQL 8 / MariaDB 10.3+ (XAMPP) |
| Composer | 2.x |
| `pdo_sqlsrv` | Chỉ cần cho lệnh đồng bộ từ ERP SQL Server |
| Node.js ≥ 20 | Chỉ để build giao diện đăng nhập (Vite) |

> **Lưu ý XAMPP:** cần bản XAMPP có PHP ≥ 8.2 (bản 8.0.x không chạy được Laravel 12; Laravel 9 đã hết hỗ trợ bảo mật).

## Cài đặt

```bash
composer install
copy .env.example .env          # rồi điền DB_USERNAME / DB_PASSWORD
php artisan key:generate
npm install && npm run build    # giao diện đăng nhập (Vite) -> public/build
php artisan migrate
php artisan erp:create-user you@company.com --name="Tên của bạn"   # hỏi mật khẩu (>= 12 ký tự)
```

Tạo database một lần: `CREATE DATABASE delivery_note_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
và một user MySQL riêng chỉ có quyền trên database đó (đừng dùng `root` ngoài máy dev).

### Chạy

- Nhanh: `php artisan serve` → http://127.0.0.1:8000
- Apache XAMPP: trỏ `DocumentRoot` về thư mục `public/`, ví dụ trong `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName erp.local
    DocumentRoot "C:/Project/Data/delivery_note_app/public"
    <Directory "C:/Project/Data/delivery_note_app/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Nạp dữ liệu khách hàng

```bash
# 1. Đồng bộ từ ERP (cần pdo_sqlsrv + điền ERP_DB_* trong .env, tài khoản chỉ có quyền SELECT)
php artisan customers:sync-erp

# 2. Hoặc dữ liệu mẫu để chạy thử (bị chặn trên production)
php artisan db:seed --class=DemoCustomerSeeder
```

Đồng bộ là *upsert* theo mã `MA001`: thêm mới, cập nhật dữ liệu gốc, **không xóa** khách hàng và **không đụng** vào bản sửa tay. Có thể chạy lặp lại / đặt lịch.

Cài `pdo_sqlsrv`: tải *Microsoft Drivers for PHP for SQL Server* đúng phiên bản PHP/TS-NTS, chép `php_pdo_sqlsrv_*.dll` vào `ext/`, thêm `extension=pdo_sqlsrv` vào `php.ini`, và cài *ODBC Driver 17/18 for SQL Server*.

## Phiếu giao hàng

Menu **Phiếu giao hàng**: danh sách (lọc theo số phiếu/mã khách/ngày) → trang phiếu (sửa phần nhập tay) → **In phiếu (A5)**.

```bash
php artisan customers:sync-erp                 # trước: khách hàng (phiếu tham chiếu tới khách)
php artisan delivery-notes:sync-erp --since=20260101   # sau: phiếu bán hàng COPTG + COPTH
php artisan db:seed                            # danh mục ĐVT + tên hàng đúng dấu (chạy lại được)
php artisan db:seed --class=DemoDeliveryNoteSeeder   # phiếu mẫu UDE (chỉ dev)
```

### Đơn đặt hàng

Menu **Đơn đặt hàng** (`php artisan orders:sync-erp`, đơn V221 / V223 / V225 từ COPTC + COPTD, chỉ đọc ERP): tìm theo số đơn, mã khách, số PO hoặc ngày.

- Trang một đơn có **bản xác nhận đơn** (logo, số đơn, khách, PO, hàng hoá, ngày hẹn giao) để chụp màn hình gửi khách trước khi giao. Nút **Hiện giá / Ẩn giá** bật tắt đơn giá, thành tiền và tổng (mặc định ẩn; `?gia=1` để mở sẵn có giá). Nút **In / lưu PDF** chỉ in bản xác nhận, khổ A4.
- Tiền lấy đúng như ERP: tiền hàng `TC029`, thuế `TC030`; khi `TC016 = 1` thì đơn giá từng dòng đã gồm thuế và bản xác nhận ghi rõ điều đó.
- Bên dưới (không nằm trong bản chụp): các phiếu giao lập từ đơn (nối qua `TH014–TH016`) với nút **In**, và số đã giao từng dòng (`TD009`, ERP chỉ tính phiếu giao đã xác nhận). Trang phiếu giao có liên kết ngược "Lập từ đơn …".

### Tự đồng bộ (không cần gõ lệnh)

Khách hàng, đơn đặt hàng và phiếu giao hàng tự đồng bộ từ ERP **5 phút một lần**. Lịch khai báo trong `routes/console.php`; mỗi lần mất khoảng 10 giây và chỉ đọc ERP. Lệnh khách hàng chạy trước, vì đơn và phiếu tham chiếu tới khách.

- **Chạy app bằng `composer run dev`**: lệnh này đã gồm tiến trình `php artisan schedule:work` (các dòng `[sync]` trong cửa sổ lệnh), nên không cần làm gì thêm.
- **Chạy app bằng Apache/XAMPP** (không dùng `composer run dev`): tạo một tác vụ trong **Windows Task Scheduler** chạy mỗi phút:
  - *Program*: đường dẫn `php.exe` (vd `C:\PHP\php-8.5.10\php.exe`); *Arguments*: `artisan schedule:run`; *Start in*: thư mục dự án.
  - *Trigger*: hằng ngày, lặp lại mỗi 1 phút. Chọn *Run whether user is logged on or not* để không hiện cửa sổ đen mỗi phút.
- Nếu ERP hoặc MySQL tạm không kết nối được, lần đó báo lỗi rồi bỏ qua, 5 phút sau tự chạy lại. Hai lần chạy không bao giờ chồng lên nhau (`withoutOverlapping`).

- **Số phiếu lấy từ ERP** (`TG001-TG002`), có ràng buộc duy nhất `(erp_type, erp_no)` nên không thể trùng; phiếu thiếu số / sai định dạng bị bỏ qua và báo số lượng khi đồng bộ.
- Cột **nhập tay** (người nhận, mã nhà cung cấp, địa chỉ, ghi chú, mã bộ phận, ghi chú dòng) không bị đồng bộ ghi đè.
- **ĐVT** chuẩn hóa qua bảng `units` + `unit_aliases` (cái ← chiếc, 个, CÁI, `chi?c`...). Đơn vị chưa có trong danh mục hiện nhãn "lạ" trên trang phiếu; thêm bằng cách bổ sung `database/seeders/UnitSeeder.php`.
- **Tên hàng đúng dấu**: sửa tên trên trang phiếu sẽ lưu theo *mã hàng* (bảng `product_names`) và dùng lại cho mọi phiếu; để trống = quay về gợi ý tự động. `ProductNameSeeder` nạp sẵn 14 tên sạch trích từ các phiếu Excel cũ.
- **In**: khổ A5 ngang, có **hai mẫu** (`config/delivery_note.php`): `excel` dựng theo sheet trong `phieu giao hang hdv.xlsx` (kích thước đo từ chính file) và `erp` dựng theo ảnh phiếu in của khách UDE. Mẫu tự chọn theo khách (`customer_templates`, mặc định `excel`); trên trang in có thể đổi mẫu bằng liên kết hoặc `?template=erp|excel`. Tự sang tờ sau khi quá số dòng của mẫu (5 / 6 dòng). Logo ở `public/images/logo.png` (lấy từ file Excel; thay bằng file gốc nếu có). Thông tin công ty ở đầu mẫu ERP nằm trong `config/delivery_note.php`.

### Từ điển sửa dấu (tự sửa chữ ERP bị mất dấu)

ERP lưu chữ bằng bảng mã GBK nên mọi chữ có dấu tiếng Việt (trừ à á è é ê ì í ò ó ù ú viết thường) bị thay bằng `?` ngay lúc nhập. App sửa lại **khi hiển thị và in**, không ghi gì vào ERP; cách sửa lưu ở bảng `accent_words` của MySQL.

- **Tự đoán** (`php artisan accent:suggest`, tự chạy mỗi giờ): gom mọi từ có `?` trong dữ liệu đã đồng bộ, rồi đoán theo
  1. khuôn chữ: `?` chỉ có thể là chữ GBK không có (`VI?T` → việt/viết, không thể là viét); gõ chữ thường thì không xét chữ HOA có dấu; hỗ trợ cả kiểu gõ "Unicode tổ hợp" (`Ma?n hi?nh` → Màn hình);
  2. độ phổ biến của từ (`resources/data/vi-words.txt`), cộng điểm lớn cho từ của ngành (`resources/data/vi-domain.txt`, chia theo địa chỉ / tên công ty / tên hàng) và từ của chính công ty (chữ còn đủ dấu, tên đã sửa tay);
  3. ngữ cảnh: cặp hai từ liền nhau khớp một cụm đã biết thì thêm mục cụm từ (`MÀU ??` → MÀU ĐỎ, `TÍNH ?? BÀN` → TÍNH ĐỂ BÀN), từ bên cạnh cũng góp điểm cho từ đơn.
  
  Chắc chắn (phương án đầu hơn hẳn) → **app tự sửa** ngay, nhãn "gợi ý". Ngang nhau → **chờ chọn**, chưa sửa.
- **Trang "Từ điển sửa dấu"** (menu bên trái): mỗi từ hiện số lần gặp, ví dụ trước/sau và các phương án; bấm một lần là áp dụng ở mọi trang và bản in, và chữ không còn bị đánh dấu "gợi ý". Có nút xác nhận một lượt mọi từ app đang tự sửa, nút **Bỏ qua** cho ký hiệu không phải chữ (vd `INTEL?` là ®), và **Đoán lại ngay**.
- **Tự học**: sửa tên khách hàng hoặc tên hàng bằng tay thì từ điển học theo (từ ngắn 1–2 chữ học kèm từ bên cạnh, vì nghĩa đổi theo ngữ cảnh: `L? M?C` = LỌ MỰC nhưng `L? CN2` = LÔ). Không bao giờ đè lên lựa chọn của người.
- Thêm từ/cụm từ của ngành: thêm dòng vào `resources/data/vi-domain.txt` dưới đúng mục `[address]`, `[company]` hoặc `[product]`, rồi bấm "Đoán lại ngay".
- Dữ liệu 11/2026: 2.613 đoạn chữ có `?`; trước đây sửa trọn được 90, nay 1.987; số chữ còn `?` giảm từ 8.027 xuống 1.154.
- `resources/data/vi-words.txt`: danh sách 50.000 từ tiếng Việt thường gặp từ [hermitdave/FrequencyWords](https://github.com/hermitdave/FrequencyWords) (dữ liệu OpenSubtitles 2018), giấy phép **CC BY-SA 4.0**.

### Kiểm tra cột ERP

Các cột `TG001–TG004`, `TH001–TH006`, `TH014–TH016`, `TD014`, `TC012` lấy từ các truy vấn có sẵn trong file COPn. Bốn cột còn lại đã đối chiếu dữ liệu thật trên `SY2019A` (11/2026): số lượng (`TH008`), đơn vị (`TH009`) và địa chỉ giao hàng (`TG008`, có thể mất dấu `?`) đều đúng; tên khách trên phiếu (`TG007`) có nhưng hầu như để trống, nên trang phiếu lấy tên theo danh mục khách hàng. Nếu đổi sang ERP khác, sửa tên cột trong `config/erp.php`.

## Kiến trúc

```
routes/web.php                      Route (auth + CSRF qua nhóm middleware 'web')
Http/Controllers/Api/CustomerController   Mỏng: nhận request -> gọi service -> trả JSON
Http/Requests/*                     Validate phía server
Services/CustomerSearchService      Tìm kiếm / lọc / phân trang
Services/CustomerNameResolver       Ghép bản sửa tay > gợi ý từ từ điển
Services/CustomerSyncService        Đồng bộ ERP -> MySQL (transaction theo lô)
Services/Erp/SqlServerCustomerSource  Đọc COPMA (chỉ SELECT) - sau interface ErpCustomerSource
Actions/UpdateCustomerNames         Lưu / xóa bản sửa tay
Support/VietnameseAccentRestorer    Khôi phục dấu theo cụm từ (config/accent_phrases.php + bảng accent_words)
Support/LostLetters                 Chữ nào có thể nằm sau một '?' (ràng buộc bảng mã GBK của ERP)
Services/AccentDictionary           Từ điển sửa dấu: nạp/cache, xác nhận, học từ lần sửa tay
Services/AccentSuggester            Tự đoán dấu cho từ lỗi (lệnh accent:suggest, mỗi giờ)
resources/views                     Layout + Blade component (toolbar, legend, table, pager, dialog)
public/css/app.css, public/js/customers/{api,render,main}.js   Giao diện (không cần build)
public/css/fonts.css, public/fonts/be-vietnam-pro/   Font Be Vietnam Pro lưu sẵn trong app (giấy phép OFL), hiện đúng cả khi không có internet
```

Bổ sung cách sửa dấu: dùng trang "Từ điển sửa dấu" (không cần sửa code). `config/accent_phrases.php` vẫn dùng được cho cụm từ viết sẵn (hai vế phải cùng số từ); mục trong bảng `accent_words` được ưu tiên hơn.

## Giao diện đăng nhập (Vite)

Trang đăng nhập là poster logistics (xe tải + phiếu bay, dựng theo `logistics-poster.html`) cạnh thẻ đăng nhập.
Mã nguồn: `resources/css/login.css`, `resources/js/login.js`, `resources/js/login/poster.js`, `resources/views/components/auth/poster.blade.php`; logo ở `public/images/poster-logo.png`.

- Sửa giao diện khi dev: `npm run dev` (hot reload). Triển khai: `npm run build`, rồi chép cả thư mục `public/build`.
- Chưa chạy `npm run build` thì trang đăng nhập báo lỗi "Vite manifest not found".
- Chữ trên poster (tên công ty, email, điện thoại) nằm trong `poster.blade.php`.

## Bảo mật

- Mọi trang/API yêu cầu đăng nhập (session), đăng nhập giới hạn 5 lần/phút, CSRF bật cho mọi request ghi.
- Tất cả đầu vào được validate ở server; truy vấn dùng Eloquent/PDO prepared statements; Blade và `esc()` ở JS chống XSS.
- `.env` đã nằm trong `.gitignore` — không commit mật khẩu / khóa.
- Hiện mọi tài khoản đăng nhập có quyền như nhau (xem + sửa tên). Cần phân quyền thì thêm vai trò khi có nhu cầu thật.

## Kiểm thử

```bash
php artisan test
```
