@extends('layouts.app')

@section('title', 'Phiếu giao hàng')
@section('heading', 'Phiếu giao hàng (đồng bộ từ ERP)')

@section('content')
    <x-delivery-notes.filter :filters="$filters" />

    <div class="table-wrap">
        <table class="table table--clickable">
            <thead>
            <tr>
                <th>Số phiếu</th>
                <th>Ngày</th>
                <th>Khách hàng</th>
                <th class="num">Số dòng</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($notes as $note)
                <tr>
                    <td><a href="{{ route('delivery-notes.show', $note) }}">{{ $note->number() }}</a></td>
                    <td>{{ $note->issued_on->format('d/m/Y') }}</td>
                    <td>{{ $note->customer_code }} – {{ $names[$note->id] }}</td>
                    <td class="num">{{ $note->lines_count }}</td>
                    <td><a href="{{ route('delivery-notes.print', $note) }}" target="_blank" rel="noopener">In</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Chưa có phiếu nào. Chạy <code>php artisan delivery-notes:sync-erp</code> để đồng bộ từ ERP.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span>{{ $notes->total() }} phiếu</span>
        <span>{{ $notes->links('pagination.simple') }}</span>
    </div>
@endsection
