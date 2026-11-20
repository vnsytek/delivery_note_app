@props(['note', 'company'])
{{-- Đầu phiếu chung của hai mẫu ERP (chung / riêng): logo, tên công ty, tiêu đề 2 ngôn ngữ, số phiếu + ngày lập. Kiểu: public/css/print-erp.css --}}
    <img class="logo" src="{{ asset('images/logo.png') }}" alt="">

    <div class="company">
        <div class="company__zh zh">{{ $company['name_zh'] }}</div>
        <div class="company__vi">{{ $company['name_vi'] }}</div>
        <div class="company__address">{{ $company['address'] }}</div>
        <div class="company__title">
            <span class="company__title-vi">Phiếu Giao Hàng</span>
            <span class="company__title-zh zh">出貨單</span>
        </div>
    </div>

    <div class="meta">
        <div class="meta__label"><span class="zh">销货单号</span> Số phiếu</div><div class="meta__value">{{ $note['erp_type'] }}- {{ $note['erp_no'] }}</div>
        <div class="meta__label"><span class="zh">制表日期</span> Ngày lập</div><div class="meta__value">{{ $note['date_iso'] }}</div>
    </div>
