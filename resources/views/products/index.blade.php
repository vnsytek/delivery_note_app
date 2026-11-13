@extends('layouts.app')

@section('title', 'Tên hàng')

@section('content')
    @php
        $tabs = ['lost' => 'Còn dấu ?', 'manual' => 'Đã sửa tay', 'all' => 'Tất cả'];
    @endphp

    <div class="page-head">
        <div>
            <h1>Tên hàng</h1>
            <p class="page-head__sub">
                Sửa tên hàng bị ERP làm mất dấu (<mark class="lost">?</mark>), tên tiếng Trung và quy cách theo mã hàng.
                Sửa một lần là dùng cho mọi phiếu giao, đơn hàng và bản in. Không thay đổi gì trong ERP.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <p class="flash flash--error" role="alert">{{ $errors->first() }}</p>
    @endif

    <nav class="tabs" aria-label="Lọc mã hàng">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('products.index', ['tab' => $key]) }}" class="tab" @if ($tab === $key) aria-current="page" @endif>
                {{ $label }} <span class="tab__count">{{ number_format($counts[$key], 0, ',', '.') }}</span>
            </a>
        @endforeach
    </nav>

    @if ($tab === 'lost')
        <p class="hint-line">
            Nhiều tên chỉ còn "?" ở một vài từ: chọn từ đó một lần ở trang
            <a href="{{ route('accents.index') }}">Từ điển sửa dấu</a> là sửa được mọi tên có từ đó. Ký hiệu ® ™ bị mất cũng hiện thành "?".
        </p>
    @endif

    <form method="GET" action="{{ route('products.index') }}" class="toolbar" role="search">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="visually-hidden" for="product-q">Tìm mã hàng</label>
        <input type="search" id="product-q" name="q" value="{{ $q }}" class="input" maxlength="60" autocomplete="off" spellcheck="false"
               placeholder="Tìm theo mã hoặc tên, gõ có dấu hay không dấu đều được…">
        <span class="toolbar__info">{{ number_format($products->total(), 0, ',', '.') }} mã</span>
    </form>

    @if ($products->isEmpty())
        <p class="empty-state">
            @if ($q !== '')
                Không có mã nào khớp “{{ $q }}”. <a href="{{ route('products.index', ['tab' => $tab]) }}">Xoá tìm kiếm</a>
            @elseif ($tab === 'lost')
                Không còn tên hàng nào bị mất dấu.
            @else
                Chưa có mã hàng nào ở đây.
            @endif
        </p>
    @else
        <ol class="word-list">
            @foreach ($products as $p)
                <li class="word product" id="{{ $p['anchor'] }}">
                    <div class="word__main">
                        <p class="word__title">
                            <span class="code" translate="no">{{ $p['item_code'] }}</span>
                            <x-status-tag :status="$p['name_status']" />
                        </p>
                        <p class="product__name"><x-lost-marks :text="$p['name']" /></p>
                        @if ($p['name_zh'])
                            <p class="product__zh">{{ $p['name_zh'] }}</p>
                        @endif
                        @if ($p['spec'])
                            <p class="word__meta">Quy cách: <x-lost-marks :text="$p['spec']" /></p>
                        @endif
                        <p class="word__meta">
                            Trên {{ number_format($p['notes'], 0, ',', '.') }} dòng phiếu giao, {{ number_format($p['orders'], 0, ',', '.') }} dòng đơn hàng
                        </p>
                        @if ($p['erp_name'] !== $p['name'])
                            <p class="word__example"><span class="word__label">Gốc ERP</span> <x-lost-marks :text="$p['erp_name']" /></p>
                        @endif
                        @if ($p['erp_spec'] !== '' && $p['erp_spec'] !== $p['spec'])
                            <p class="word__example"><span class="word__label">Quy cách ERP</span> <x-lost-marks :text="$p['erp_spec']" /></p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('products.update') }}" class="product-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="item_code" value="{{ $p['item_code'] }}">
                        <label class="product-form__field">
                            <span class="product-form__label">Tên tiếng Việt</span>
                            <input type="text" name="name" maxlength="255" class="input input--cell" autocomplete="off"
                                   value="{{ $p['name'] }}" placeholder="{{ $p['name_suggested'] }}">
                        </label>
                        <label class="product-form__field">
                            <span class="product-form__label">Tên tiếng Trung</span>
                            <input type="text" name="name_zh" maxlength="255" class="input input--cell input--zh" autocomplete="off"
                                   value="{{ $p['name_zh'] }}" placeholder="In dưới tên Việt, có thể để trống">
                        </label>
                        <label class="product-form__field">
                            <span class="product-form__label">Quy cách</span>
                            <input type="text" name="spec" maxlength="255" class="input input--cell" autocomplete="off" spellcheck="false"
                                   value="{{ $p['spec'] }}">
                        </label>
                        <div class="product-form__actions">
                            <button type="submit" name="action" value="save" class="btn btn--sm btn--primary">Lưu</button>
                            @if ($p['edited'])
                                <button type="submit" name="action" value="reset" class="link-button"
                                        title="Bỏ phần sửa tay, dùng lại tên gợi ý tự động từ ERP">Quay về gợi ý</button>
                            @endif
                        </div>
                    </form>
                </li>
            @endforeach
        </ol>

        <div class="pager">
            <span>{{ $products->firstItem() }}–{{ $products->lastItem() }} trong {{ number_format($products->total(), 0, ',', '.') }} mã</span>
            {{ $products->links('pagination.simple') }}
        </div>
    @endif
@endsection
