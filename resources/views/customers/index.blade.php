@extends('layouts.app')

@section('title', 'Khách hàng')

@section('content')
    <div class="page-head">
        <div>
            <h1>Khách hàng</h1>
            <p class="page-head__sub">
                {{ $counts['total'] }} khách hàng từ ERP.
                @if ($counts['review'])
                    {{ $counts['partial'] }} tên còn ký tự <mark class="lost">?</mark> chưa khôi phục được,
                    {{ $counts['review'] - $counts['partial'] }} tên là gợi ý tự động nên kiểm tra lại.
                @else
                    Tất cả tên đã đúng dấu.
                @endif
                Bấm vào một khách để sửa tên.
            </p>
        </div>
    </div>

    <div id="customers-app"
         data-list-url="{{ route('api.customers.index') }}"
         data-show-url="{{ route('api.customers.show', '__id__') }}"
         data-update-url="{{ route('api.customers.names.update', '__id__') }}"
         data-login-url="{{ route('login') }}"
         data-review="{{ $reviewOnly ? '1' : '0' }}">

        <x-customers.toolbar :counts="$counts" :review-only="$reviewOnly" />
        <p id="error" class="error" role="alert"></p>
        <x-customers.table />
        <x-customers.pager />
        <x-customers.legend />
    </div>

    <x-customers.dialog />
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/customers/main.js') }}"></script>
@endpush
