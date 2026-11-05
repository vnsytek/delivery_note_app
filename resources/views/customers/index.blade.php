@extends('layouts.app')

@section('heading', 'Danh sách khách hàng (ERP – chỉ đọc, hiển thị đã khôi phục dấu tiếng Việt)')

@section('content')
    <div id="customers-app"
         data-list-url="{{ route('api.customers.index') }}"
         data-show-url="{{ route('api.customers.show', '__id__') }}"
         data-update-url="{{ route('api.customers.names.update', '__id__') }}"
         data-login-url="{{ route('login') }}">

        <x-customers.toolbar />
        <x-customers.legend />
        <p id="error" class="error" role="alert"></p>
        <x-customers.table />
        <x-customers.pager />
    </div>

    <x-customers.dialog />
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/customers/main.js') }}"></script>
@endpush
