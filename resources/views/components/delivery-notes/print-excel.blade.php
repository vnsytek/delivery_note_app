@props(['note', 'lines', 'company', 'page', 'pages'])
@php
    // Mẫu Excel luôn có vùng bảng tối thiểu: thêm dòng trống có viền cho đủ MIN_ROWS dòng, như file Excel.
    $blankRows = max(0, 6 - count($lines));
@endphp
{{-- Mẫu Excel: dựng theo sheet của "phieu giao hang hdv.xlsx". Kích thước xem public/css/print-excel.css --}}
<section class="sheet tpl-excel">
    <img class="logo" src="{{ asset('images/logo.png') }}" alt="">

    <table class="xl">
        <colgroup>
            <col style="width:19.5mm"><col style="width:23.9mm"><col style="width:50.6mm"><col style="width:14.9mm">
            <col style="width:17.4mm"><col style="width:19.5mm"><col style="width:34.5mm"><col style="width:17.7mm">
        </colgroup>
        <tr class="r-small"><td colspan="8"></td></tr>
        <tr class="r-small"><td colspan="8"></td></tr>
        <tr class="r-title">
            <td colspan="2"></td>
            <td colspan="3" class="title zh">出貨單</td>
            <td class="zh">销貨單號</td>
            <td colspan="2">{{ $note['erp_type'] }}-{{ $note['erp_no'] }}</td>
        </tr>
        <tr class="r-line">
            <td colspan="5"></td>
            <td class="zh">製表日期:</td>
            <td colspan="2">{{ $note['date'] }}</td>
        </tr>
        <tr class="r-gap"><td colspan="8"></td></tr>

        <tr class="r-line box-top">
            <td class="box-left zh">客户名稱</td>
            <td colspan="5" class="strong">{{ $note['customer_code'] }}- {{ $note['customer_full'] }}</td>
            <td colspan="2" class="box-right"><span class="zh">客户電話</span>:{{ $note['receiver'] }}</td>
        </tr>
        <tr class="r-address">
            <td class="box-left zh">收货地址</td>
            <td colspan="7" class="box-right left">{{ $note['address'] }}</td>
        </tr>
        <tr class="r-line box-bottom">
            <td class="box-left zh">姓</td>
            <td colspan="5"></td>
            <td colspan="2" class="box-right"><span class="zh">單據日期</span></td>
        </tr>

        <tr class="r-head">
            <th><span class="zh">序號</span><br>STT</th>
            <th><span class="zh">品號</span><br>MÃ LIỆU</th>
            <th><span class="zh">品名</span><br>TÊN HÀNG</th>
            <th><span class="zh">規格</span><br>QUY CÁCH</th>
            <th><span class="zh">数量</span><br>SL</th>
            <th><span class="zh">單位</span><br>ĐVT</th>
            <th class="zh">客單號</th>
            <th class="zh">备注</th>
        </tr>
        @foreach ($lines as $line)
            <tr class="r-data">
                <td>{{ $line['seq'] }}</td>
                <td>{{ $line['item_code'] }}</td>
                <td><x-delivery-notes.breakable :text="$line['name']" /></td>
                <td><x-delivery-notes.breakable :text="$line['spec']" /></td>
                <td>{{ $line['quantity'] }}</td>
                <td>{{ $line['unit'] }}</td>
                <td>{!! nl2br(e($line['customer_po'])) !!}</td>
                <td>{{ $line['remark'] }}</td>
            </tr>
        @endforeach
        @for ($i = 0; $i < $blankRows; $i++)
            <tr class="r-data"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor

        <tr class="r-foot">
            <td rowspan="2" class="foot-customer zh">客户签收</td>
            <td colspan="3" rowspan="2" class="foot-blank"></td>
            <td colspan="2" class="foot-label zh">倉庫</td>
            <td colspan="2" class="foot-label zh">填表</td>
        </tr>
        <tr class="r-foot-sign">
            <td colspan="2" class="foot-box"></td>
            <td colspan="2" class="foot-box"></td>
        </tr>
    </table>

    @if ($pages > 1)<span class="page-number">{{ $page }}/{{ $pages }}</span>@endif
</section>
