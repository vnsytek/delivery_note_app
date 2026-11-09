// Hàm dựng HTML thuần (không đụng DOM, không gọi mạng). Mọi dữ liệu đều qua esc() để chống XSS.

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ESCAPES[c]);

// Tô nổi các '?' (chữ có dấu bị ERP làm mất). Escape trước, nên chèn <mark> sau vẫn an toàn.
export const lost = value => esc(value).replace(/\?/g, '<mark class="lost">?</mark>');

const TAGS = {
  manual: ['đã sửa tay', 'chip--manual'],
  auto: ['gợi ý', 'chip--auto'],
  partial: ['còn ?', 'chip--partial'],
};

export const tag = status =>
  TAGS[status] ? `<span class="chip ${TAGS[status][1]}">${TAGS[status][0]}</span>` : '';

// Hiện bản đúng dấu; nếu khác bản gốc thì ghi bản gốc nhỏ bên dưới, chữ mất dấu được tô nổi.
const nameCell = (row, field) => {
  const fixed = row[field + '_VI'];
  const original = row[field];
  return `${lost(fixed)}${tag(row.status[field])}` +
    (fixed !== original ? `<span class="original">Gốc trong ERP: ${lost(original)}</span>` : '');
};

// Cả dòng bấm được bằng chuột; với bàn phím, mã khách là một nút thật (Tab tới, Enter để mở).
export const customerRows = items => items.map(row =>
  `<tr data-id="${esc(row.MA001)}">` +
  `<td><button type="button" class="code-button" translate="no" aria-label="Sửa tên khách ${esc(row.MA001)}">${esc(row.MA001)}</button></td>` +
  `<td>${nameCell(row, 'MA002')}</td><td>${nameCell(row, 'MA003')}</td></tr>`
).join('');

export const emptyRow = (text, columns = 3) =>
  `<tr class="empty-row"><td colspan="${columns}" class="empty-state">${esc(text)}</td></tr>`;

const MAX_LENGTH = { MA002: 255, MA003: 500 };

const editField = (customer, field, label) => `
  <div class="field">
    <label class="field__label" for="field-${field}">${label}${tag(customer.status[field])}</label>
    <input type="text" class="input" id="field-${field}" maxlength="${MAX_LENGTH[field]}" autocomplete="off"
           value="${esc(customer[field + '_VI'])}" aria-describedby="original-${field}">
    <span class="field__hint" id="original-${field}">Gốc trong ERP: ${lost(customer[field]) || '—'}</span>
  </div>`;

export const editForm = customer =>
  editField(customer, 'MA002', 'Tên viết tắt') + editField(customer, 'MA003', 'Tên đầy đủ');

export const detailRows = customer => Object.entries(customer)
  .filter(([key, value]) => !key.endsWith('_VI') && key !== 'status' && value !== '' && value !== null)
  .map(([key, value]) => `<tr><th scope="row">${esc(key)}</th><td>${esc(value)}</td></tr>`).join('');
