@props(['note', 'lines', 'company', 'page', 'pages'])
@php
    // Mẫu Excel luôn có vùng bảng tối thiểu: thêm dòng trống có viền cho đủ 6 dòng như file Excel, nhưng không để
    // dòng đệm làm tràn tờ khi các dòng hàng đã cao (ước tính chiều cao còn lại theo PrintPaginator).
    $paginator = app(\App\Services\PrintPaginator::class);
    $room = config('delivery_note.templates.excel.layout.budget_mm') - $paginator->usedHeight($lines, 'excel');
    $blankRows = max(0, min(6 - count($lines), (int) floor($room / 4.76)));
@endphp
{{-- Mẫu Excel: dựng theo sheet của "phieu giao hang hdv.xlsx". Kích thước xem public/css/print-excel.css --}}
<section class="sheet tpl-excel">
    <img class="logo" src="{{ asset('images/logo.png') }}" alt="">

    <table class="xl">
        <colgroup>
            {{-- 9 cột, tổng 198 mm. Quy cách rộng hơn Excel gốc (14,9 mm) vì nay chứa cả chữ Việt lẫn chữ Trung; thêm 客戶品號 --}}
            <col style="width:12mm"><col style="width:24mm"><col style="width:40mm"><col style="width:34mm">
            <col style="width:12mm"><col style="width:12mm"><col style="width:27mm"><col style="width:24mm"><col style="width:13mm">
        </colgroup>
        <tr class="r-top"><td colspan="9"></td></tr>
        <tr class="r-vi">
            <td colspan="2"></td>
            <td colspan="7" class="title-vi">Phiếu Giao Hàng</td>
        </tr>
        <tr class="r-title">
            <td colspan="2"></td>
            <td colspan="2" class="title zh">出貨單</td>
            <td colspan="2" class="zh">销貨單號</td>
            <td colspan="3">{{ $note['erp_type'] }}-{{ $note['erp_no'] }}</td>
        </tr>
        <tr class="r-line">
            <td colspan="4"></td>
            <td colspan="2" class="zh">製表日期:</td>
            <td colspan="3">{{ $note['date'] }}</td>
        </tr>
        <tr class="r-gap"><td colspan="9"></td></tr>

        <tr class="r-line box-top">
            <td colspan="2" class="box-left zh">客户名稱</td>
            <td colspan="4" class="strong">{{ $note['customer_code'] }}- {{ $note['customer_full'] }}</td>
            <td colspan="3" class="box-right"><span class="zh">客户電話</span>:{{ $note['receiver'] }}</td>
        </tr>
        <tr class="r-address">
            <td colspan="2" class="box-left zh">收货地址</td>
            <td colspan="7" class="box-right left">{{ $note['address'] }}</td>
        </tr>
        <tr class="r-line box-bottom">
            <td colspan="2" class="box-left zh">姓</td>
            <td colspan="4"></td>
            <td colspan="3" class="box-right"><span class="zh">單據日期</span></td>
        </tr>

        <tr class="r-head">
            <th><span class="zh">序號</span><br>STT</th>
            <th><span class="zh">品號</span><br>MÃ LIỆU</th>
            <th><span class="zh">品名</span><br>TÊN HÀNG</th>
            <th><span class="zh">規格</span><br>QUY CÁCH</th>
            <th><span class="zh">数量</span><br>SL</th>
            <th><span class="zh">單位</span><br>ĐVT</th>
            <th class="zh">客單號</th>
            <th><span class="zh">客戶品號</span><br>Mã hàng khách</th>
            <th class="zh">备注</th>
        </tr>
        @foreach ($lines as $line)
            <tr class="r-data">
                <td>{{ $line['seq'] }}</td>
                <td>{{ $line['item_code'] }}</td>
                <td>
                    <x-delivery-notes.breakable :text="$line['name']" />
                    @if ($line['name_zh'])<br><span class="zh">{{ $line['name_zh'] }}</span>@endif
                </td>
                <td>
                    <x-delivery-notes.breakable :text="$line['spec']" />
                    @if ($line['spec_zh'])<br><span class="zh">{{ $line['spec_zh'] }}</span>@endif
                </td>
                <td>{{ $line['quantity'] }}</td>
                <td>{{ $line['unit'] }}</td>
                <td>{!! nl2br(e($line['customer_po'])) !!}</td>
                <td>{{ $line['customer_item_code'] }}</td>
                <td>{{ $line['remark'] }}</td>
            </tr>
        @endforeach
        @for ($i = 0; $i < $blankRows; $i++)
            <tr class="r-data"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor

        <tr class="r-foot">
            <td colspan="2" rowspan="2" class="foot-customer zh">客户签收</td>
            <td colspan="2" rowspan="2" class="foot-blank"></td>
            <td colspan="2" class="foot-label zh">倉庫</td>
            <td colspan="3" class="foot-label zh">填表</td>
        </tr>
        <tr class="r-foot-sign">
            <td colspan="2" class="foot-box"></td>
            <td colspan="3" class="foot-box"></td>
        </tr>
    </table>

    @if ($pages > 1)<span class="page-number">{{ $page }}/{{ $pages }}</span>@endif
</section>
