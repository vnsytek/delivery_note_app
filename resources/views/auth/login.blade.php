@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
    <main class="auth__layout">
        <section class="auth__poster-panel">
            {{-- Thương hiệu neo ở góc trên trái của trang (không co theo poster) --}}
            <div class="auth__brand">
                <img class="auth__brand-logo" src="{{ asset('images/poster-logo.png') }}" alt="">
                <span class="auth__brand-name">CÔNG TY TNHH SANG Y</span>
            </div>
            <div class="auth__poster">
                <x-auth.poster />
            </div>
        </section>

        <section class="login-panel">
            <form method="POST" action="{{ route('login') }}" class="login-form" data-folder>
                @csrf
                <svg class="login-form__edge" aria-hidden="true"><path d=""/></svg>
                <div class="login-form__tab" data-folder-tab>Hệ thống nhập phiếu giao hàng</div>
                <h1 class="login-form__title">Đăng nhập</h1>
                <p class="login-form__lead">Đăng nhập để tạo và quản lý phiếu giao hàng của bạn.</p>

                @error('email')
                    <p class="login-form__error" role="alert">{{ $message }}</p>
                @enderror

                <div class="login-field">
                    <label class="login-field__label" for="email">Tên đăng nhập</label>
                    <div class="login-input">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="username" placeholder="Nhập email đăng nhập" class="login-input__control">
                    </div>
                </div>

                <div class="login-field">
                    <div class="login-field__row">
                        <label class="login-field__label" for="password">Mật khẩu</label>
                        {{-- Chưa có chức năng tự đặt lại mật khẩu: liên hệ quản trị để được cấp lại. --}}
                        <a class="login-field__link" href="mailto:Ha@vnsytek.com?subject=Quên mật khẩu">Quên mật khẩu?</a>
                    </div>
                    <div class="login-input login-input--password">
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               placeholder="Nhập mật khẩu" class="login-input__control">
                        <button type="button" class="login-input__toggle" data-toggle-password="#password"
                                aria-label="Hiện mật khẩu" aria-pressed="false">
                            <svg class="icon-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                        </button>
                    </div>
                </div>

                <label class="login-form__remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Ghi nhớ đăng nhập
                </label>

                <button type="submit" class="login-form__submit">
                    Đăng nhập
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>

                <p class="login-form__help">Cần hỗ trợ? Liên hệ 0397-040-799</p>
            </form>
        </section>
    </main>
@endsection
