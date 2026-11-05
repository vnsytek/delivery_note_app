// Biến thẻ kính thành hình thư mục: tab nhô lên ở góc trên trái (rộng vừa đủ chứa nhãn), thân bo góc.
// Hình cắt (clip-path) và đường viền SVG dùng chung một đường path tính theo kích thước thật của thẻ.

const RADIUS = 18;   // bo góc
const TAB_HEIGHT = 30; // phần tab nhô lên trên thân
const SLANT = 16;    // độ dài đoạn cong nối tab xuống thân

function folderPath(width, height, tabWidth) {
  const r = RADIUS;
  const t = TAB_HEIGHT;
  const tabEnd = Math.min(tabWidth, width - r - SLANT); // x nơi tab bắt đầu hạ xuống thân
  return [
    `M ${r} 0`,
    `L ${tabEnd - SLANT} 0`,
    `C ${tabEnd} 0 ${tabEnd} ${t} ${tabEnd + SLANT} ${t}`,
    `L ${width - r} ${t}`,
    `A ${r} ${r} 0 0 1 ${width} ${t + r}`,
    `L ${width} ${height - r}`,
    `A ${r} ${r} 0 0 1 ${width - r} ${height}`,
    `L ${r} ${height}`,
    `A ${r} ${r} 0 0 1 0 ${height - r}`,
    `L 0 ${r}`,
    `A ${r} ${r} 0 0 1 ${r} 0`,
    'Z',
  ].join(' ');
}

export function initFolder(form) {
  const tab = form.querySelector('[data-folder-tab]');
  const edge = form.querySelector('.login-form__edge path');
  if (!tab || !edge || !('ResizeObserver' in window)) {
    return; // không hỗ trợ: giữ thẻ kính bo góc thường
  }

  const draw = () => {
    const path = folderPath(form.offsetWidth, form.offsetHeight, tab.offsetWidth + SLANT);
    form.style.clipPath = `path('${path}')`;
    edge.setAttribute('d', path);
    form.classList.add('is-folder');
  };

  const observer = new ResizeObserver(draw);
  observer.observe(form);
  observer.observe(tab);
  draw();
}
