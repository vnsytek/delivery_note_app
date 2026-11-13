@extends('layouts.app')

@section('title', 'Đơn đặt hàng')

@section('content')
    <div class="page-head">
        <div>
            <h1>Đơn đặt hàng</h1>
            <p class="page-head__sub">
                Hôm nay có {{ $stats['today'] }} đơn.
                @if ($stats['since'])
                    Tổng cộng {{ number_format($stats['total'], 0, ',', '.') }} đơn từ ngày {{ $stats['since']->format('d/m/Y') }}, tự cập nhật từ ERP.
                @endif
                Mở một đơn để chụp gửi khách.
            </p>
        </div>
    </div>

    <x-delivery-notes.filter :filters="$filters" :action="route('orders.index')" label="Tìm đơn"
                             placeholder="Số đơn (vd V221-2611), mã khách hoặc số PO…" />

    @if ($orders->isEmpty())
        <p class="empty-state">
            @if (array_filter($filters))
                Không có đơn nào khớp bộ lọc. <a href="{{ route('orders.index') }}">Xoá bộ lọc</a>
            @else
                Chưa có đơn nào. Đơn sẽ tự xuất hiện sau lần đồng bộ ERP tiếp theo (5 phút một lần).
            @endif
        </p>
    @else
        <div class="table-wrap">
            <table class="table table--notes table--stack">
                <thead>
                <tr>
                    <th scope="col" class="col-number">Số đơn</th>
                    <th scope="col">Khách hàng</th>
                    <th scope="col" class="num">Số dòng</th>
                    <th scope="col" class="cell-actions">Tình trạng</th>
                </tr>
                </thead>
                @foreach ($groups as $group)
                    <tbody>
                    <tr class="group-row">
                        <th colspan="4" scope="rowgroup">{{ $group['label'] }}<span class="muted">{{ $group['count'] }} đơn</span></th>
                    </tr>
                    @foreach ($group['orders'] as $order)
                        <tr>
                            <td>
                                <a class="doc-link" href="{{ route('orders.show', $order) }}" translate="no">{{ $order->number() }}</a>
                                <span class="sub">{{ config('delivery_note.order_type_labels.'.$order->erp_type, $order->erp_type) }}</span>
                            </td>
                            <td>
                                <span class="code" translate="no">{{ $order->customer_code }}</span>
                                <span class="customer-name"><x-lost-marks :text="$rows[$order->id]['name']" /></span>
                                @if ($order->customer_po)
                                    <span class="sub">PO: {{ $order->customer_po }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $order->lines->count() }}<span class="unit"> dòng</span></td>
                            <td class="cell-actions">
                                <span class="chip chip--{{ $rows[$order->id]['status']['tone'] }}">{{ $rows[$order->id]['status']['text'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>

        <div class="pager">
            <span>{{ $orders->firstItem() }}–{{ $orders->lastItem() }} trong {{ number_format($orders->total(), 0, ',', '.') }} đơn</span>
            {{ $orders->links('pagination.simple') }}
        </div>
    @endif
@endsection
