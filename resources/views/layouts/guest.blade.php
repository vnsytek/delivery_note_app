<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Đăng nhập')</title>
    @vite(['resources/css/login.css', 'resources/js/login.js'])
</head>
<body class="auth">
    @yield('content')
</body>
</html>
