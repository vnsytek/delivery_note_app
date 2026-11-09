@extends('layouts.app')

@section('title', 'Phiếu '.$note['number'])
@section('heading', 'Phiếu giao hàng '.$note['number'])

@section('content')
    <form method="POST" action="{{ route('delivery-notes.update', $note['id']) }}" class="card">
        @csrf
        @method('PUT')

        <dl class="facts">
            <dt>Số phiếu (ERP)</dt><dd>{{ $note['number'] }}</dd>
            <dt>Ngày</dt><dd>{{ $note['date'] }}</dd>
            <dt>Khách hàng</dt><dd>{{ $note['customer_code'] }} – {{ $note['customer_short'] }} – {{ $note['customer_full'] }}</dd>
        </dl>

        <div class="form-grid">
            <label class="field">
                <span class="field__label">Người nhận (收貨人)</span>
                <input type="text" name="receiver" maxlength="100" class="input" value="{{ old('receiver', $note['receiver']) }}">
            </label>
            <label class="field">
                <span class="field__label">Mã nhà cung cấp tại khách (供應商代)</span>
                <input type="text" name="supplier_code" maxlength="50" class="input" value="{{ old('supplier_code', $note['supplier_code']) }}">
            </label>
            <label class="field field--wide">
                <span class="field__label">Địa chỉ giao hàng (để trống = lấy theo ERP)</span>
                <input type="text" name="address_manual" maxlength="500" class="input" placeholder="{{ $note['address'] }}" value="{{ old('address_manual', $note['address_manual']) }}">
            </label>
            <label class="field field--wide">
                <span class="field__label">Ghi chú</span>
                <input type="text" name="note" maxlength="500" class="input" value="{{ old('note', $note['note']) }}">
            </label>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>STT</th><th>Mã liệu</th><th>Tên hàng (Việt / Trung)</th><th>Quy cách</th>
                    <th class="num">SL</th><th>ĐVT</th><th>PO</th><th>Mã hàng khách</th><th>Mã bộ phận</th><th>Ghi chú</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($note['lines'] as $line)
                    <tr>
                        <td>{{ $line['seq'] }}</td>
                        <td>{{ $line['item_code'] }}</td>
                        <td class="cell-name">
                            <input type="text" name="lines[{{ $line['id'] }}][name]" maxlength="255" class="input input--cell"
                                   value="{{ old('lines.'.$line['id'].'.name', $line['name']) }}">
                            <x-status-tag :status="$line['name_status']" />
                            <input type="text" name="lines[{{ $line['id'] }}][name_zh]" maxlength="255" class="input input--cell input--zh"
                                   placeholder="Tên tiếng Trung (in dưới tên Việt)"
                                   value="{{ old('lines.'.$line['id'].'.name_zh', $line['name_zh']) }}">
                            @if ($line['erp_name'] !== '' && $line['erp_name'] !== $line['name'])
                                <div class="cell-original">gốc: {{ $line['erp_name'] }}</div>
                            @endif
                        </td>
                        <td>{{ $line['spec'] }}</td>
                        <td class="num">{{ $line['quantity'] }}</td>
                        <td>
                            {{ $line['unit'] }}
                            @unless ($line['unit_known'])<span class="tag tag--partial" title="Chưa có trong danh mục ĐVT">lạ</span>@endunless
                        </td>
                        <td>{{ $line['customer_po'] }}</td>
                        <td>{{ $line['customer_item_code'] }}</td>
                        <td><input type="text" name="lines[{{ $line['id'] }}][dept_code]" maxlength="30" class="input input--cell"
                                   value="{{ old('lines.'.$line['id'].'.dept_code', $line['dept_code']) }}"></td>
                        <td><input type="text" name="lines[{{ $line['id'] }}][remark]" maxlength="255" class="input input--cell"
                                   value="{{ old('lines.'.$line['id'].'.remark', $line['remark']) }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif

        <p class="actions">
            <a href="{{ route('delivery-notes.index') }}" class="btn">Quay lại</a>
            <button type="submit" class="btn btn--primary">Lưu</button>
            <a href="{{ route('delivery-notes.print', $note['id']) }}" target="_blank" rel="noopener" class="btn btn--primary">In phiếu (A5)</a>
        </p>
    </form>
@endsection
