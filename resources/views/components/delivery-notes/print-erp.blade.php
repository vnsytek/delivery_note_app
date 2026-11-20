@props(['note', 'lines', 'company', 'page', 'pages'])
{{-- Mẫu ERP riêng: đầu phiếu + các cột riêng của khách (PO / Mã hàng khách / Mã bộ phận) như ảnh phiếu UDE; Tên hàng và Quy cách tách riêng như mẫu chung. Kiểu: public/css/print-erp.css --}}
<section class="sheet tpl-erp">
    <x-delivery-notes.erp-header :note="$note" :company="$company" />

    <div class="body">
        <table class="box">
            <tr>
                <th rowspan="2" class="zh">客户名稱</th>
                <td class="strong">{{ $note['customer_short'] }}{{ $note['customer_short'] !== '' ? '-' : '' }}</td>
            </tr>
            <tr><td class="strong">{{ $note['customer_full'] }}</td></tr>
            <tr><th class="zh">收货人:</th><td>{{ $note['receiver'] }}</td></tr>
            <tr><th class="zh">供应商代</th><td>{{ $note['supplier_code'] }}</td></tr>
        </table>

        <table class="items">
            <colgroup>
                <col style="width:14mm"><col style="width:28mm"><col style="width:39mm"><col style="width:27mm"><col style="width:10mm">
                <col style="width:10mm"><col style="width:24mm"><col style="width:24mm"><col style="width:18mm">
            </colgroup>
            <thead>
            <tr>
                <th><span class="zh">序號</span><br>STT</th>
                <th><span class="zh">品號</span><br>MÃ LIỆU SANG Y</th>
                <th><span class="zh">品名</span><br>TÊN HÀNG</th>
                <th><span class="zh">规格</span><br>QUY CÁCH</th>
                <th><span class="zh">数量</span><br>SL</th>
                <th><span class="zh">單位</span><br>ĐVT</th>
                <th><span class="zh">采购单号</span><br>PO No.</th>
                <th><span class="zh">料号</span><br>Mã hàng {{ $note['customer_code'] }}</th>
                <th><span class="zh">部门编号</span><br>Mã bộ phận</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td class="center">{{ $line['seq'] }}</td>
                    <td class="center">{{ $line['item_code'] }}</td>
                    <td class="center">
                        <x-delivery-notes.breakable :text="$line['name']" />
                        @if ($line['name_zh'])<br><span class="zh">{{ $line['name_zh'] }}</span>@endif
                    </td>
                    <td class="center">
                        <x-delivery-notes.breakable :text="$line['spec']" />
                        @if ($line['spec_zh'])<br><span class="zh">{{ $line['spec_zh'] }}</span>@endif
                    </td>
                    <td class="center">{{ $line['quantity'] }}</td>
                    <td class="center">{{ $line['unit'] }}</td>
                    <td class="center">{{ $line['customer_po'] }}</td>
                    <td class="center">{{ $line['customer_item_code'] }}</td>
                    <td class="center">{{ $line['dept_code'] }}</td>
                </tr>
            @endforeach
            </tbody>
            {{-- Chân phiếu nằm trong cùng bảng để đường kẻ khớp đúng với các cột phía trên --}}
            <tfoot>
                <tr class="sign-top">
                    <td rowspan="2" class="sign-customer zh">客户签收</td>
                    <td colspan="6" rowspan="2" class="sign-blank"></td>
                    <td colspan="2" class="sign-label zh">倉庫</td>
                </tr>
                <tr class="sign-bottom">
                    <td colspan="2" class="sign-box"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($pages > 1)<span class="page-number">{{ $page }}/{{ $pages }}</span>@endif
</section>
