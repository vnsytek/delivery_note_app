// Hàm dựng HTML thuần (không đụng DOM, không gọi mạng). Mọi dữ liệu đều qua esc() để chống XSS.

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ESCAPES[c]);

const TAGS = {
  manual: ['đã sửa tay', 'tag--manual'],
  auto: ['gợi ý', 'tag--auto'],
  partial: ['còn ?', 'tag--partial'],
};

export const tag = status =>
  TAGS[status] ? `<span class="tag ${TAGS[status][1]}">${TAGS[status][0]}</span>` : '';

// Hiện bản đúng dấu; nếu khác bản gốc thì ghi bản gốc nhỏ bên dưới.
const nameCell = (row, field) => {
  const fixed = row[field + '_VI'];
  const original = row[field];
  return `${esc(fixed)}${tag(row.status[field])}` +
    (fixed !== original ? `<div class="cell-original">gốc: ${esc(original)}</div>` : '');
};

export const customerRows = items => items.map(row =>
  `<tr data-id="${esc(row.MA001)}"><td>${esc(row.MA001)}</td><td>${nameCell(row, 'MA002')}</td><td>${nameCell(row, 'MA003')}</td></tr>`
).join('');

const MAX_LENGTH = { MA002: 255, MA003: 500 };

const editRow = (customer, field, label) =>
  `<tr><td>${label}<div class="cell-original">gốc: ${esc(customer[field])}</div></td>
    <td><input type="text" class="input" id="field-${field}" maxlength="${MAX_LENGTH[field]}" value="${esc(customer[field + '_VI'])}"> ${tag(customer.status[field])}</td></tr>`;

export const editForm = customer =>
  editRow(customer, 'MA002', 'Tên viết tắt') + editRow(customer, 'MA003', 'Tên đầy đủ');

export const detailRows = customer => Object.entries(customer)
  .filter(([key, value]) => !key.endsWith('_VI') && key !== 'status' && value !== '' && value !== null)
  .map(([key, value]) => `<tr><td>${esc(key)}</td><td>${esc(value)}</td></tr>`).join('');
