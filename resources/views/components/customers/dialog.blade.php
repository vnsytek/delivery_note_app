<dialog id="dialog" class="dialog" aria-labelledby="dialog-title">
    <form id="dialog-form" method="dialog">
        <div class="dialog__body">
            <h2 id="dialog-title" class="dialog__title"></h2>
            <p class="dialog__lead">Sửa tên cho đúng dấu. Bản sửa chỉ lưu trong app này, không ghi vào ERP; để trống một ô thì quay về gợi ý tự động.</p>
            <div id="edit-form"></div>
            <p id="dialog-message" class="dialog__message" role="status"></p>
            <details>
                <summary>Các cột khác của khách này trong ERP</summary>
                <table class="kv" id="detail"></table>
            </details>
        </div>
        <div class="dialog__actions">
            <button type="button" id="close" class="btn">Đóng</button>
            <button type="submit" id="save" class="btn btn--primary">Lưu tên</button>
        </div>
    </form>
</dialog>
{{-- Luôn nằm trong trang (không dùng hidden) để trình đọc màn hình đọc được khi có chữ mới. --}}
<p id="toast" class="toast" role="status" aria-live="polite"></p>
