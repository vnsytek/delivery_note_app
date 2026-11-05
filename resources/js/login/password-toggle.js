// Nút con mắt: bật/tắt hiển thị mật khẩu của ô nhập được chỉ định bởi data-toggle-password.
export function initPasswordToggle(button) {
    const input = document.querySelector(button.dataset.togglePassword);
    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
}
