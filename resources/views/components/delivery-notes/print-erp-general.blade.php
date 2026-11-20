@props(['note', 'lines', 'company', 'page', 'pages'])
{{-- Mẫu ERP chung: đầu phiếu ERP + bảng 9 cột (sheet Excel chung + 客戶品號 = COPTD.TD014) (Tên hàng và Quy cách riêng), chân 客户签收 | 倉庫 | 填表. Kiểu: public/css/print-erp.css --}}
<section class="sheet tpl-erp tpl-erp-general">
    <x-delivery-notes.erp-header :note="$note" :company="$company" />

    <div class="body">
        <table class="box">
            <tr>
                <th rowspan="2" class="zh">客户名稱</th>
                <td class="strong">{{ $note['customer_short'] }}{{ $note['customer_short'] !== '' ? '-' : '' }}</td>
            </tr>
            <tr><td class="strong">{{ $note['customer_full'] }}</td></tr>
            <tr><th class="zh">收货人:</th><td class="wrap">{{ $note['receiver'] }}</td></tr>
            <tr><th class="zh">收货地址</th><td class="wrap">{{ $note['address'] }}</td></tr>
        </table>

        <table class="items">
            {{-- 9 cột (tổng 194 mm): tên hàng và quy cách TÁCH RIÊNG như Excel chung, thêm 客戶品號 (mã hàng của khách) cạnh 客單號 --}}
            <colgroup>
                <col style="width:13mm"><col style="width:22mm"><col style="width:38mm"><col style="width:28mm">
                <col style="width:11mm"><col style="width:11mm"><col style="width:25mm"><col style="width:25mm"><col style="width:21mm">
            </colgroup>
            <thead>
            <tr>
                <th><span class="zh">序號</span><br>STT</th>
                <th><span class="zh">品號</span><br>MÃ LIỆU</th>
                <th><span class="zh">品名</span><br>TÊN HÀNG</th>
                <th><span class="zh">規格</span><br>QUY CÁCH</th>
                <th><span class="zh">数量</span><br>SL</th>
                <th><span class="zh">單位</span><br>ĐVT</th>
                <th><span class="zh">客單號</span><br>Số đơn khách</th>
                <th><span class="zh">客戶品號</span><br>Mã hàng khách</th>
                <th><span class="zh">备注</span><br>Ghi chú</th>
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
                    <td class="center">{!! nl2br(e($line['customer_po'])) !!}</td>
                    <td class="center">{{ $line['customer_item_code'] }}</td>
                    <td class="center">{{ $line['remark'] }}</td>
                </tr>
            @endforeach
            </tbody>
            {{-- Chân phiếu: 倉庫 dưới hai cột SL + ĐVT, 填表 dưới ba cột 客單號 + 客戶品號 + 备注 --}}
            <tfoot>
                <tr class="sign-top">
                    <td rowspan="2" class="sign-customer zh">客户签收</td>
                    <td colspan="3" rowspan="2" class="sign-blank"></td>
                    <td colspan="2" class="sign-label zh">倉庫</td>
                    <td colspan="3" class="sign-label zh">填表</td>
                </tr>
                <tr class="sign-bottom">
                    <td colspan="2" class="sign-box"></td>
                    <td colspan="3" class="sign-box"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($pages > 1)<span class="page-number">{{ $page }}/{{ $pages }}</span>@endif
</section>
