import { createApi } from './api.js';
import { customerRows, detailRows, editForm, emptyRow } from './render.js';

const PAGE_SIZE = 25;
const SEARCH_DELAY_MS = 300;
const SLOW_MS = 300;
const TOAST_MS = 3200;

const $ = id => document.getElementById(id);
const app = $('customers-app');
const api = createApi({
  listUrl: app.dataset.listUrl,
  showUrl: app.dataset.showUrl,
  updateUrl: app.dataset.updateUrl,
  loginUrl: app.dataset.loginUrl,
});

// Trạng thái lọc nằm trên URL (?q=&review=1&page=2) nên dán lại liên kết vẫn mở đúng chỗ.
const params = new URLSearchParams(window.location.search);
const state = {
  offset: Math.max(0, (parseInt(params.get('page') ?? '1', 10) || 1) - 1) * PAGE_SIZE,
  review: app.dataset.review === '1',
};
$('search').value = params.get('q') ?? '';

let searchTimer;
let editingId = null;
let lastRequest = 0;
let toastTimer;

const number = value => value.toLocaleString('vi-VN');

function syncUrl() {
  const url = new URL(window.location.href);
  const query = $('search').value.trim();
  const page = Math.floor(state.offset / PAGE_SIZE) + 1;
  if (query) url.searchParams.set('q', query); else url.searchParams.delete('q');
  if (state.review) url.searchParams.set('review', '1'); else url.searchParams.delete('review');
  if (page > 1) url.searchParams.set('page', String(page)); else url.searchParams.delete('page');
  history.replaceState(null, '', url);
}

async function loadList() {
  const request = ++lastRequest; // bỏ kết quả cũ về muộn khi người dùng gõ nhanh
  const table = $('rows').closest('table');
  const slow = setTimeout(() => { $('info').textContent = 'Đang tải…'; }, SLOW_MS);
  $('error').textContent = '';
  table.setAttribute('aria-busy', 'true');
  try {
    const { total, items } = await api.list({
      query: $('search').value,
      review: state.review,
      limit: PAGE_SIZE,
      offset: state.offset,
    });
    if (request !== lastRequest) return;

    $('rows').innerHTML = items.length
      ? customerRows(items)
      : emptyRow(state.review ? 'Không còn tên nào cần kiểm tra dấu.' : 'Không tìm thấy khách hàng nào khớp. Thử gõ ít chữ hơn.');
    const from = total ? state.offset + 1 : 0;
    const to = Math.min(state.offset + PAGE_SIZE, total);
    $('info').textContent = `${number(total)} khách hàng`;
    $('page-info').textContent = total ? `${from}–${to} trong ${number(total)}` : '';
    $('prev').disabled = state.offset === 0;
    $('next').disabled = state.offset + PAGE_SIZE >= total;
    syncUrl();
  } catch (e) {
    if (request === lastRequest) {
      $('info').textContent = '';
      $('error').textContent = `Không tải được danh sách khách hàng (${e.message}). Kiểm tra kết nối rồi tải lại trang.`;
    }
  } finally {
    clearTimeout(slow);
    if (request === lastRequest) table.removeAttribute('aria-busy');
  }
}

async function refreshReviewCount() {
  try {
    const { total } = await api.list({ query: '', review: true, limit: 1, offset: 0 });
    $('count-review').textContent = number(total);
  } catch { /* con số cũ vẫn dùng được */ }
}

function setFilter(review) {
  state.review = review;
  state.offset = 0;
  document.querySelectorAll('[data-filter]').forEach(button => {
    button.setAttribute('aria-pressed', String((button.dataset.filter === 'review') === review));
  });
  loadList();
}

function showMessage(text, isError = false) {
  $('dialog-message').textContent = text;
  $('dialog-message').classList.toggle('dialog__message--error', isError);
}

function toast(text) {
  const el = $('toast');
  el.textContent = text;
  el.classList.add('is-visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('is-visible'), TOAST_MS);
}

async function openDetail(id) {
  try {
    const customer = await api.show(id);
    editingId = customer.MA001;
    $('dialog-title').textContent = 'Sửa tên khách ' + customer.MA001;
    showMessage('');
    $('edit-form').innerHTML = editForm(customer);
    $('detail').innerHTML = detailRows(customer);
    $('dialog').showModal();
    $('field-MA003').focus();
  } catch (e) {
    $('error').textContent = `Không mở được khách hàng ${id} (${e.message}). Thử bấm lại.`;
  }
}

async function saveNames(event) {
  event.preventDefault();
  const button = $('save');
  button.disabled = true;
  button.textContent = 'Đang lưu…';
  try {
    await api.updateNames(editingId, {
      MA002: $('field-MA002').value,
      MA003: $('field-MA003').value,
    });
    $('dialog').close();
    toast(`Đã lưu tên khách ${editingId}.`);
    loadList();
    refreshReviewCount();
  } catch (e) {
    showMessage(`Chưa lưu được (${e.message}). Kiểm tra kết nối rồi bấm Lưu tên lần nữa.`, true);
  } finally {
    button.disabled = false;
    button.textContent = 'Lưu tên';
  }
}

$('search').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { state.offset = 0; loadList(); }, SEARCH_DELAY_MS);
});
document.querySelectorAll('[data-filter]').forEach(button => {
  button.addEventListener('click', () => setFilter(button.dataset.filter === 'review'));
});
$('prev').addEventListener('click', () => { state.offset = Math.max(0, state.offset - PAGE_SIZE); loadList(); });
$('next').addEventListener('click', () => { state.offset += PAGE_SIZE; loadList(); });
// Bấm vào bất kỳ đâu trên dòng (hoặc nút mã khách bằng bàn phím) đều mở hộp thoại sửa tên.
$('rows').addEventListener('click', event => {
  const row = event.target.closest('tr[data-id]');
  if (row) openDetail(row.dataset.id);
});
$('dialog-form').addEventListener('submit', saveNames);
$('close').addEventListener('click', () => $('dialog').close());

// Chỉ tự đặt con trỏ vào ô tìm trên máy có chuột (trên điện thoại sẽ bật bàn phím che màn hình).
if (window.matchMedia('(pointer: fine)').matches) $('search').focus();
loadList();
