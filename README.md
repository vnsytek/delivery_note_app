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

- **Số phiếu lấy từ ERP** (`TG001-TG002`), có ràng buộc duy nhất `(erp_type, erp_no)` nên không thể trùng; phiếu thiếu số / sai định dạng bị bỏ qua và báo số lượng khi đồng bộ.
- Cột **nhập tay** (người nhận, mã nhà cung cấp, địa chỉ, ghi chú, mã bộ phận, ghi chú dòng) không bị đồng bộ ghi đè.
- **ĐVT** chuẩn hóa qua bảng `units` + `unit_aliases` (cái ← chiếc, 个, CÁI, `chi?c`...). Đơn vị chưa có trong danh mục hiện nhãn "lạ" trên trang phiếu; thêm bằng cách bổ sung `database/seeders/UnitSeeder.php`.
- **Tên hàng đúng dấu**: sửa tên trên trang phiếu sẽ lưu theo *mã hàng* (bảng `product_names`) và dùng lại cho mọi phiếu; để trống = quay về gợi ý tự động. `ProductNameSeeder` nạp sẵn 14 tên sạch trích từ các phiếu Excel cũ.
- **In**: khổ A5 ngang, có **hai mẫu** (`config/delivery_note.php`): `excel` dựng theo sheet trong `phieu giao hang hdv.xlsx` (kích thước đo từ chính file) và `erp` dựng theo ảnh phiếu in của khách UDE. Mẫu tự chọn theo khách (`customer_templates`, mặc định `excel`); trên trang in có thể đổi mẫu bằng liên kết hoặc `?template=erp|excel`. Tự sang tờ sau khi quá số dòng của mẫu (5 / 6 dòng). Logo ở `public/images/logo.png` (lấy từ file Excel; thay bằng file gốc nếu có). Thông tin công ty ở đầu mẫu ERP nằm trong `config/delivery_note.php`.

### Kiểm tra cột ERP (làm một lần khi có kết nối)

Các cột `TG001–TG004`, `TH001–TH006`, `TH014–TH016`, `TD014`, `TC012` lấy từ các truy vấn có sẵn trong file COPn. Bốn cột sau **chưa kiểm chứng** trên `SY2019A` và có thể phải sửa trong `config/erp.php`: địa chỉ giao hàng (`TG008`), tên khách trên phiếu (`TG007`), số lượng (`TH008`), đơn vị (`TH009`). Nếu sai, lệnh đồng bộ báo lỗi SQL "Invalid column name".

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
Support/VietnameseAccentRestorer    Khôi phục dấu (từ điển: config/accent_phrases.php)
resources/views                     Layout + Blade component (toolbar, legend, table, pager, dialog)
public/css/app.css, public/js/customers/{api,render,main}.js   Giao diện (không cần build)
```

Bổ sung thêm cụm từ khôi phục dấu: thêm một dòng vào `config/accent_phrases.php` (hai vế phải cùng số từ).

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
