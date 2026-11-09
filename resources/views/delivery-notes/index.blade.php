@extends('layouts.app')

@section('title', 'Phiếu giao hàng')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phiếu giao hàng</h1>
            <p class="page-head__sub">
                Hôm nay có {{ $stats['today'] }} phiếu.
                @if ($stats['since'])
                    Tổng cộng {{ number_format($stats['total'], 0, ',', '.') }} phiếu từ ngày {{ $stats['since']->format('d/m/Y') }}, tự cập nhật từ ERP.
                @endif
            </p>
        </div>
    </div>

    <x-delivery-notes.filter :filters="$filters" />

    @if ($notes->isEmpty())
        <p class="empty-state">
            @if (array_filter($filters))
                Không có phiếu nào khớp bộ lọc. <a href="{{ route('delivery-notes.index') }}">Xoá bộ lọc</a>
            @else
                Chưa có phiếu nào. Phiếu sẽ tự xuất hiện sau lần đồng bộ ERP tiếp theo (5 phút một lần).
            @endif
        </p>
    @else
        <div class="table-wrap">
            <table class="table table--notes table--stack">
                <thead>
                <tr>
                    <th scope="col" class="col-number">Số phiếu</th>
                    <th scope="col">Khách hàng</th>
                    <th scope="col" class="num">Số dòng</th>
                    <th scope="col" class="cell-actions"><span class="visually-hidden">In</span></th>
                </tr>
                </thead>
                @foreach ($groups as $group)
                    <tbody>
                    <tr class="group-row">
                        <th colspan="4" scope="rowgroup">{{ $group['label'] }}<span class="muted">{{ $group['count'] }} phiếu</span></th>
                    </tr>
                    @foreach ($group['notes'] as $note)
                        <tr>
                            <td>
                                <a class="doc-link" href="{{ route('delivery-notes.show', $note) }}">{{ $note->number() }}</a>
                                <span class="sub">{{ config('delivery_note.type_labels.'.$note->erp_type, $note->erp_type) }}</span>
                            </td>
                            <td>
                                <span class="code">{{ $note->customer_code }}</span>
                                <span class="customer-name"><x-lost-marks :text="$names[$note->id]" /></span>
                                @if ($note->receiver)
                                    <span class="sub">Người nhận: {{ $note->receiver }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $note->lines_count }}<span class="unit"> dòng</span></td>
                            <td class="cell-actions">
                                <a class="btn btn--sm" href="{{ route('delivery-notes.print', $note) }}" target="_blank" rel="noopener"
                                   aria-label="In phiếu {{ $note->number() }}">In</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>

        <div class="pager">
            <span>{{ $notes->firstItem() }}–{{ $notes->lastItem() }} trong {{ number_format($notes->total(), 0, ',', '.') }} phiếu</span>
            {{ $notes->links('pagination.simple') }}
        </div>
    @endif
@endsection
