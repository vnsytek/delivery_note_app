// Animation của poster đăng nhập: co theo khung chứa, vệt gió sau đuôi xe, phiếu hóa đơn bay ra từ thùng xe.

const STAGE_SIZE = 1080;
const rnd = (a, b) => a + Math.random() * (b - a);

function fitToContainer(poster) {
  const apply = () => poster.style.setProperty('--scale', poster.clientWidth / STAGE_SIZE);
  new ResizeObserver(apply).observe(poster);
  apply();
}

function addWind(box) {
  for (let i = 0; i < 9; i++) {
    const streak = document.createElement('div');
    const duration = rnd(1.4, 2.6);
    streak.className = 'streak';
    streak.style.width = rnd(70, 180) + 'px';
    streak.style.height = rnd(2, 4) + 'px';
    streak.style.top = rnd(520, 890) + 'px';
    streak.style.animationDuration = duration + 's';
    streak.style.animationDelay = -Math.random() * duration + 's';
    box.appendChild(streak);
  }
}

function addFlyingPapers(box) {
  for (let i = 0; i < 10; i++) {
    const duration = rnd(5, 8);
    const fly = document.createElement('div');
    fly.className = 'fly';
    fly.style.left = rnd(250, 400) + 'px';
    fly.style.top = rnd(540, 700) + 'px';
    fly.style.setProperty('--dx', -rnd(380, 720) + 'px');
    fly.style.setProperty('--dy', -rnd(120, 380) + 'px');
    fly.style.animationDuration = duration + 's';
    fly.style.animationDelay = -rnd(0, duration) + 's';

    const sway = document.createElement('div');
    sway.className = 'sway';
    sway.style.setProperty('--sw', rnd(15, 45) + 'px');
    sway.style.animationDuration = rnd(1.1, 2) + 's';
    sway.style.animationDelay = -rnd(0, 2) + 's';

    const width = rnd(46, 84);
    const paper = document.createElement('div');
    paper.className = 'paper';
    paper.style.width = width + 'px';
    paper.style.height = width * 1.35 + 'px';
    paper.style.setProperty('--td', rnd(1.2, 2.6) + 's');
    paper.style.setProperty('--tdl', -rnd(0, 2) + 's');
    paper.style.setProperty('--r0', rnd(-50, 10) + 'deg');
    paper.style.setProperty('--r1', rnd(-10, 60) + 'deg');
    paper.style.setProperty('--ax', rnd(30, 75) + 'deg');
    paper.style.setProperty('--ay', rnd(30, 80) + 'deg');
    paper.innerHTML = '<b>HÓA ĐƠN</b><i></i>';

    sway.appendChild(paper);
    fly.appendChild(sway);
    box.appendChild(fly);
  }
}

// Bấm (hoặc Enter/Space) vào xe tải: bật/tắt đèn pha; mặc định tắt. Trạng thái nằm ở lớp is-lit của poster.
function addTruckLights(poster) {
  const truck = poster.querySelector('[data-truck]');
  if (!truck) {
    return;
  }
  const toggle = () => {
    const lit = poster.classList.toggle('is-lit');
    truck.setAttribute('aria-pressed', String(lit));
    // Làm tối cả nền trang (không chỉ khung poster) để không lộ viền hình vuông.
    poster.closest('.auth__layout')?.classList.toggle('is-lit', lit);
  };
  truck.addEventListener('click', toggle);
  truck.addEventListener('keydown', event => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      toggle();
    }
  });
}

export function initPoster(poster) {
  fitToContainer(poster);
  addWind(poster.querySelector('[data-wind]'));
  addFlyingPapers(poster.querySelector('[data-papers]'));
  addTruckLights(poster);
}
