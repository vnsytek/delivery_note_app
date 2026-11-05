<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Khách hàng ERP')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header">
    <span class="site-header__title">@yield('heading')</span>
    @auth
        <nav class="site-nav">
            <a href="{{ route('customers.index') }}" @class(['is-active' => request()->routeIs('customers.*')])>Khách hàng</a>
            <a href="{{ route('delivery-notes.index') }}" @class(['is-active' => request()->routeIs('delivery-notes.*')])>Phiếu giao hàng</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--light">Đăng xuất</button>
        </form>
    @endauth
</header>
<main class="container">
    @if (session('status'))
        <p class="flash" role="status">{{ session('status') }}</p>
    @endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
