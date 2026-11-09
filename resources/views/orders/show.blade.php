@extends('layouts.app')

@section('title', 'Đơn '.$order['number'])

@section('content')
    @php
        $money = fn ($value) => number_format(round((float) $value), 0, ',', '.');
    @endphp

    <nav class="crumbs" aria-label="Đường dẫn">
        <a href="{{ route('orders.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Đơn đặt hàng
        </a>
    </nav>

    <div class="page-head">
        <div>
            <h1 translate="no">{{ $order['number'] }}</h1>
            <p class="page-head__sub">
                {{ $order['type_label'] }} ngày {{ $order['date'] }}
                <span class="chip chip--{{ $order['status']['tone'] }}">{{ $order['status']['text'] }}</span>
            </p>
        </div>
        <div class="page-head__actions">
            <a href="{{ route('orders.show', [$order['id'], 'gia' => $showPrices ? null : 1]) }}" class="btn" data-price-toggle
               aria-pressed="{{ $showPrices ? 'true' : 'false' }}">{{ $showPrices ? 'Ẩn giá' : 'Hiện giá' }}</a>
            <button type="button" class="btn btn--primary" data-print-order>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
                In / lưu PDF
            </button>
        </div>
    </div>

    <p class="hint-line">Khung bên dưới là bản xác nhận đơn để chụp màn hình gửi khách. Giá đang <strong data-price-state>{{ $showPrices ? 'hiện' : 'ẩn' }}</strong>.</p>

    {{-- Bản xác nhận đơn: chỉ chứa thông tin dành cho khách (không có tình trạng nội bộ). --}}
    <article @class(['order-card', 'show-prices' => $showPrices]) id="order-card" aria-label="Bản xác nhận đơn {{ $order['number'] }}">
        <header class="order-card__head">
            <div class="order-card__company">
                <img src="{{ asset('images/poster-logo.png') }}" alt="" width="48" height="48">
                <div>
                    <p class="order-card__company-name" translate="no">{{ $company['name_vi'] }}</p>
                    <p class="order-card__company-zh" translate="no">{{ $company['name_zh'] }}</p>
                    <p class="order-card__company-address">{{ $company['address'] }}</p>
                </div>
            </div>
            <div class="order-card__title">
                <p class="order-card__doc">Xác nhận đơn đặt hàng <span class="zh">訂單</span></p>
                <dl class="order-card__meta">
                    <dt>Số đơn</dt><dd translate="no">{{ $order['number'] }}</dd>
                    <dt>Ngày đặt</dt><dd>{{ $order['date'] }}</dd>
                </dl>
            </div>
        </header>

        <dl class="order-card__customer">
            <div>
                <dt>Khách hàng</dt>
                <dd><span translate="no">{{ $order['customer_code'] }}</span> – {{ $order['customer_full'] ?: $order['customer_short'] }}</dd>
            </div>
            <div>
                <dt>Số PO của khách</dt>
                <dd translate="no">{{ $order['customer_po'] ?: '—' }}</dd>
            </div>
        </dl>

        <div class="table-wrap">
            <table class="table order-card__table">
                <thead>
                <tr>
                    <th scope="col" class="col-seq">STT</th>
                    <th scope="col">Hàng hoá</th>
                    <th scope="col" class="num">Số lượng</th>
                    <th scope="col">Ngày giao</th>
                    <th scope="col" class="num price">Đơn giá</th>
                    <th scope="col" class="num price">Thành tiền</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($order['lines'] as $line)
                    <tr>
                        <td class="col-seq">{{ (int) $line['seq'] }}</td>
                        <td>
                            <span class="order-card__item">{{ $line['name'] }}</span>
                            <span class="sub">
                                Mã <span translate="no">{{ $line['item_code'] }}</span>@if ($line['spec']), quy cách {{ $line['spec'] }}@endif
                                @if ($line['customer_item_code']), mã của quý khách <span translate="no">{{ $line['customer_item_code'] }}</span>@endif
                            </span>
                        </td>
                        <td class="num nowrap">{{ $line['quantity'] }} {{ $line['unit'] }}</td>
                        <td class="nowrap">{{ $line['due'] ?? '—' }}</td>
                        <td class="num nowrap price">{{ $money($line['unit_price']) }}</td>
                        <td class="num nowrap price">{{ $money($line['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Đơn không còn dòng hàng nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="order-card__totals price">
            <dl>
                <dt>Tiền hàng</dt><dd>{{ $money($order['amount']) }} đ</dd>
                <dt>Thuế GTGT {{ $order['tax_rate'] }}%</dt><dd>{{ $money($order['tax']) }} đ</dd>
                <dt class="order-card__grand">Tổng cộng</dt><dd class="order-card__grand">{{ $money($order['total']) }} đ</dd>
            </dl>
            @if ($order['tax_included'])
                <p class="sub">Đơn giá và thành tiền từng dòng đã gồm thuế GTGT.</p>
            @endif
        </div>
    </article>

    {{-- Phần nội bộ: không nằm trong bản chụp gửi khách. --}}
    <section class="order-internal" aria-labelledby="h-notes">
        <h2 id="h-notes" class="section-title">Phiếu giao của đơn này<span class="muted">{{ $deliveryNotes->count() }} phiếu</span></h2>
        @if ($deliveryNotes->isEmpty())
            <p class="empty-state empty-state--inline">Chưa có phiếu giao nào lập từ đơn này. Phiếu sẽ hiện ở đây sau khi lập trong ERP và đồng bộ (5 phút một lần).</p>
        @else
            <div class="table-wrap">
                <table class="table table--notes table--stack">
                    <thead>
                    <tr>
                        <th scope="col" class="col-number">Số phiếu</th>
                        <th scope="col">Ngày giao</th>
                        <th scope="col" class="num">Số dòng</th>
                        <th scope="col" class="cell-actions"><span class="visually-hidden">In</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($deliveryNotes as $note)
                        <tr>
                            <td><a class="doc-link" href="{{ route('delivery-notes.show', $note) }}" translate="no">{{ $note->number() }}</a></td>
                            <td>{{ $note->issued_on->format('d/m/Y') }}</td>
                            <td class="num">{{ $note->lines_count }}<span class="unit"> dòng</span></td>
                            <td class="cell-actions">
                                <a class="btn btn--sm" href="{{ route('delivery-notes.print', $note) }}" target="_blank" rel="noopener"
                                   aria-label="In phiếu {{ $note->number() }}">In</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h2 class="section-title section-title--spaced">Đã giao theo dòng</h2>
        <p class="hint-line">Số đã giao lấy từ ERP, chỉ tính các phiếu giao đã được xác nhận trong ERP.</p>
        <ul class="progress-list">
            @foreach ($order['lines'] as $line)
                <li>
                    <span class="progress-list__name"><span class="muted">{{ (int) $line['seq'] }}.</span> {{ $line['name'] }}</span>
                    <span class="progress-list__value">
                        Đã giao {{ $line['delivered'] }}/{{ $line['quantity'] }} {{ $line['unit'] }}
                        @if ($line['done'])
                            <span class="chip chip--manual">xong</span>
                        @else
                            <span class="chip chip--neutral">còn {{ $line['remaining'] }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
@endsection

@push('scripts')
    <script>
        // Bật/tắt giá không cần tải lại trang; URL giữ ?gia=1 để mở lại đúng trạng thái.
        (function () {
            var card = document.getElementById('order-card');
            var toggle = document.querySelector('[data-price-toggle]');
            var state = document.querySelector('[data-price-state]');
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var show = !card.classList.contains('show-prices');
                card.classList.toggle('show-prices', show);
                toggle.setAttribute('aria-pressed', String(show));
                toggle.textContent = show ? 'Ẩn giá' : 'Hiện giá';
                state.textContent = show ? 'hiện' : 'ẩn';
                var url = new URL(window.location.href);
                if (show) url.searchParams.set('gia', '1'); else url.searchParams.delete('gia');
                history.replaceState(null, '', url);
            });
            document.querySelector('[data-print-order]').addEventListener('click', function () { window.print(); });
        })();
    </script>
@endpush
