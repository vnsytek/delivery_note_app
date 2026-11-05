// Gọi API JSON của ứng dụng. Tự gắn CSRF token và chuyển về trang đăng nhập khi hết phiên.

const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

export function createApi({ listUrl, showUrl, updateUrl, loginUrl }) {
  const withId = (template, id) => template.replace('__id__', encodeURIComponent(id));

  async function request(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      },
    });
    if (response.status === 401 || response.status === 419) {
      window.location.href = loginUrl;
      throw new Error('Phiên đăng nhập đã hết hạn');
    }
    if (!response.ok) throw new Error('Lỗi ' + response.status);
    return response.json();
  }

  return {
    list: ({ query, review, limit, offset }) =>
      request(`${listUrl}?${new URLSearchParams({ q: query, review: review ? 1 : 0, limit, offset })}`),
    show: id => request(withId(showUrl, id)),
    updateNames: (id, names) =>
      request(withId(updateUrl, id), { method: 'PUT', body: JSON.stringify(names) }),
  };
}
