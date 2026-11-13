<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#eef2f6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Phiếu giao hàng') – Sang Y</title>
    <link rel="preload" href="{{ asset('fonts/be-vietnam-pro/BeVietnamPro-400-vietnamese.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a class="skip-link" href="#main">Bỏ qua menu, tới nội dung chính</a>
<div class="shell">
    <aside class="side" id="side-nav" aria-label="Menu chính">
        <a class="brand" href="{{ route('delivery-notes.index') }}">
            <img src="{{ asset('images/poster-logo.png') }}" alt="" width="30" height="30">
            <span class="brand__text">
                <span class="brand__name" translate="no">Sang Y</span>
                <span class="brand__scope">Phiếu giao hàng từ ERP</span>
            </span>
        </a>
        @auth
            <nav class="nav">
                <a href="{{ route('orders.index') }}" @class(['nav-item', 'is-active' => request()->routeIs('orders.*')])
                   @if (request()->routeIs('orders.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M8 12h8M8 16h5"/></svg>
                    Đơn đặt hàng
                </a>
                <a href="{{ route('delivery-notes.index') }}" @class(['nav-item', 'is-active' => request()->routeIs('delivery-notes.*')])
                   @if (request()->routeIs('delivery-notes.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    Phiếu giao hàng
                </a>
                <a href="{{ route('customers.index') }}" @class(['nav-item', 'is-active' => request()->routeIs('customers.*')])
                   @if (request()->routeIs('customers.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21V7l9-4 9 4v14"/><path d="M9 21v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/></svg>
                    Khách hàng
                </a>
                <a href="{{ route('products.index') }}" @class(['nav-item', 'is-active' => request()->routeIs('products.*')])
                   @if (request()->routeIs('products.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
                    Tên hàng
                </a>
                <a href="{{ route('accents.index') }}" @class(['nav-item', 'is-active' => request()->routeIs('accents.*')])
                   @if (request()->routeIs('accents.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19 9 6l5 13M5.8 15h6.4"/><path d="M15 6.5 17.5 4 20 6.5M15.5 19h4.5"/><path d="M17.75 10v9"/></svg>
                    Từ điển sửa dấu
                </a>
            </nav>
            <div class="side__foot">
                <span class="side__user" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link-button">Đăng xuất</button>
                </form>
            </div>
        @endauth
    </aside>
    <div class="nav-backdrop" data-nav-close></div>

    <header class="topbar">
        <button type="button" class="topbar__menu" aria-controls="side-nav" aria-expanded="false" data-nav-toggle>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <span class="visually-hidden">Mở menu</span>
        </button>
        <a class="topbar__brand" href="{{ route('delivery-notes.index') }}">
            <img src="{{ asset('images/poster-logo.png') }}" alt="" width="24" height="24">Sang Y
        </a>
        {{-- Tìm phiếu từ mọi trang; trang danh sách phiếu đã có ô tìm riêng nên ẩn ô này đi. --}}
        @unless (request()->routeIs('delivery-notes.index'))
            <form class="topbar__search" method="GET" action="{{ route('delivery-notes.index') }}" role="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <label class="visually-hidden" for="global-q">Tìm phiếu giao hàng</label>
                <input id="global-q" type="search" name="q" maxlength="50" autocomplete="off" spellcheck="false"
                       placeholder="Tìm phiếu: số phiếu hoặc mã khách…">
            </form>
        @else
            <span class="topbar__spacer"></span>
        @endunless
        @if ($erpSyncedAt)
            <p class="topbar__sync" title="Tự đồng bộ từ ERP 5 phút một lần">
                ERP cập nhật {{ $erpSyncedAt->isToday() ? $erpSyncedAt->format('H:i') : $erpSyncedAt->format('H:i d/m') }}
            </p>
        @endif
        @auth
            <div class="topbar__account">
                <span class="topbar__user" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--sm">Đăng xuất</button>
                </form>
            </div>
        @endauth
    </header>

    <main class="sheet" id="main">
        @if (session('status'))
            <p class="flash" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</div>

@stack('scripts')
<script>
    // Menu trên điện thoại: mở/đóng thanh bên, Esc hoặc chạm nền để đóng.
    (function () {
        var root = document.documentElement;
        var toggle = document.querySelector('[data-nav-toggle]');
        function setOpen(open) {
            root.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            if (open) { var first = document.querySelector('#side-nav a'); if (first) first.focus(); }
        }
        toggle.addEventListener('click', function () { setOpen(!root.classList.contains('nav-open')); });
        document.querySelector('[data-nav-close]').addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root.classList.contains('nav-open')) { setOpen(false); toggle.focus(); }
        });
    })();
</script>
</body>
</html>
