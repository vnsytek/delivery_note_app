@props(['note', 'lines', 'company', 'page', 'pages'])
{{-- Mẫu ERP: dựng theo ảnh phiếu in (khách UDE). Kích thước xem public/css/print-erp.css --}}
<section class="sheet tpl-erp">
    <img class="logo" src="{{ asset('images/logo.png') }}" alt="">

    <div class="company">
        <div class="company__zh zh">{{ $company['name_zh'] }}</div>
        <div class="company__vi">{{ $company['name_vi'] }}</div>
        <div class="company__address">{{ $company['address'] }}</div>
        <div class="company__title"><span class="zh">出貨單</span> Phiếu Giao Hàng</div>
    </div>

    <div class="meta">
        <div class="meta__label zh">销货单号</div><div class="meta__value">{{ $note['erp_type'] }}- {{ $note['erp_no'] }}</div>
        <div class="meta__label zh">制表日期:</div><div class="meta__value">{{ $note['date_iso'] }}</div>
    </div>

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
                <col style="width:16.8mm"><col style="width:28.4mm"><col style="width:63.2mm"><col style="width:10.3mm">
                <col style="width:9.3mm"><col style="width:24.3mm"><col style="width:24.8mm"><col style="width:17mm">
            </colgroup>
            <thead>
            <tr>
                <th><span class="zh">序號</span><br>STT</th>
                <th><span class="zh">品號</span><br>MÃ LIỆU SANG Y</th>
                <th><span class="zh">规格</span><br>Quy cách</th>
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
                    <td class="left">
                        <x-delivery-notes.breakable :text="$line['name']" />
                        @if ($line['spec'])<br><x-delivery-notes.breakable :text="$line['spec']" />@endif
                    </td>
                    <td class="center">{{ $line['quantity'] }}</td>
                    <td class="center">{{ $line['unit'] }}</td>
                    <td class="center">{{ $line['customer_po'] }}</td>
                    <td class="center bottom">{{ $line['customer_item_code'] }}</td>
                    <td class="center">{{ $line['dept_code'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="sign">
            <div class="sign__customer zh">客户签收</div>
            <div class="sign__blank"></div>
            <div class="sign__warehouse">
                <div class="sign__warehouse-label zh">倉庫</div>
                <div class="sign__warehouse-box"></div>
            </div>
        </div>
    </div>

    @if ($pages > 1)<span class="page-number">{{ $page }}/{{ $pages }}</span>@endif
</section>
