import { createApi } from './api.js';
import { customerRows, detailRows, editForm } from './render.js';

const PAGE_SIZE = 25;
const SEARCH_DELAY_MS = 300;

const $ = id => document.getElementById(id);
const app = $('customers-app');
const api = createApi({
  listUrl: app.dataset.listUrl,
  showUrl: app.dataset.showUrl,
  updateUrl: app.dataset.updateUrl,
  loginUrl: app.dataset.loginUrl,
});

let offset = 0;
let searchTimer;
let editingId = null;

async function loadList() {
  $('error').textContent = '';
  try {
    const { total, items } = await api.list({
      query: $('search').value,
      review: $('review').checked,
      limit: PAGE_SIZE,
      offset,
    });
    $('rows').innerHTML = customerRows(items);
    const from = total ? offset + 1 : 0;
    $('info').textContent = `${from}–${Math.min(offset + PAGE_SIZE, total)} / ${total} khách hàng`;
    $('prev').disabled = offset === 0;
    $('next').disabled = offset + PAGE_SIZE >= total;
  } catch (e) {
    $('error').textContent = 'Không tải được dữ liệu: ' + e.message;
  }
}

function showMessage(text, isError = false) {
  $('dialog-message').textContent = text;
  $('dialog-message').classList.toggle('dialog__message--error', isError);
}

async function openDetail(id) {
  try {
    const customer = await api.show(id);
    editingId = customer.MA001;
    $('dialog-title').textContent = 'Khách hàng ' + customer.MA001;
    showMessage('');
    $('edit-form').innerHTML = editForm(customer);
    $('detail').innerHTML = detailRows(customer);
    $('dialog').showModal();
  } catch (e) {
    $('error').textContent = 'Không tải được chi tiết: ' + e.message;
  }
}

async function saveNames() {
  try {
    await api.updateNames(editingId, {
      MA002: $('field-MA002').value,
      MA003: $('field-MA003').value,
    });
    $('dialog').close();
    loadList();
  } catch (e) {
    showMessage('Lưu thất bại', true);
  }
}

$('search').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { offset = 0; loadList(); }, SEARCH_DELAY_MS);
});
$('review').addEventListener('change', () => { offset = 0; loadList(); });
$('prev').addEventListener('click', () => { offset = Math.max(0, offset - PAGE_SIZE); loadList(); });
$('next').addEventListener('click', () => { offset += PAGE_SIZE; loadList(); });
$('rows').addEventListener('click', event => {
  const row = event.target.closest('tr');
  if (row) openDetail(row.dataset.id);
});
$('save').addEventListener('click', saveNames);
$('close').addEventListener('click', () => $('dialog').close());

loadList();
