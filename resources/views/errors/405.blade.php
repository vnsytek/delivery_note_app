@extends('errors::minimal')

@php($allowed = $exception->getHeaders()['Allow'] ?? null)
@section('title', 'Sai phương thức yêu cầu')
@section('code', '405')
@section('message')
    Sai phương thức yêu cầu: {{ request()->method() }} /{{ request()->path() }}@if ($allowed) (chỉ cho phép {{ $allowed }})@endif.
    <span style="display:block;margin-top:.5rem;font-size:.875rem;text-transform:none;letter-spacing:0">
        Tải lại trang bằng Ctrl+F5 rồi thử lại. Nếu vẫn lỗi, gửi cho quản trị đúng dòng trên.
    </span>
@endsection
