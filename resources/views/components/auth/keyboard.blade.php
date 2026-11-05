{{-- Bàn phím nghiêng ở góc trên bên phải poster + hai phiếu nhỏ lơ lửng. Kiểu dáng: resources/css/login.css (.keyboard, .kb-card) --}}
@php
    // [độ lệch trái, [độ rộng từng phím]] của mỗi hàng; đơn vị SVG, phím cao 52, khoảng cách 8.
    $rows = [
        [0,   [52, 52, 52, 52, 52, 52, 52, 52, 52, 52, 52]],
        [26,  [52, 52, 52, 52, 52, 52, 52, 52, 52, 52]],
        [44,  [52, 52, 52, 52, 52, 52, 52, 52, 52]],
        [0,   [100, 52, 52, 52, 52, 52, 52, 52, 52]],
        [80,  [64, 64, 300]],
    ];
    $labels = ['4-0' => 'S', '4-1' => 'Y']; // 'hàng-phím' => chữ: các phím trắng viết tắt SY
    $gap = 8;
    $size = 52;
@endphp
<svg class="keyboard" viewBox="0 0 720 340" aria-hidden="true">
    <defs>
        <linearGradient id="key-face" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#5d6ba4"/>
            <stop offset="1" stop-color="#47548b"/>
        </linearGradient>
        <linearGradient id="key-white" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#e9e6dc"/>
        </linearGradient>
    </defs>
    @foreach ($rows as $r => [$offset, $widths])
        @php($x = $offset)
        @foreach ($widths as $k => $w)
            @php($y = $r * ($size + $gap))
            @php($label = $labels[$r.'-'.$k] ?? null)
            @php($lit = $label !== null)
            <rect x="{{ $x }}" y="{{ $y + 6 }}" width="{{ $w }}" height="{{ $size }}" rx="11" fill="{{ $lit ? '#c9c4b4' : '#1a2250' }}" opacity="{{ $lit ? 1 : .6 }}"/>
            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $size }}" rx="11" fill="url(#{{ $lit ? 'key-white' : 'key-face' }})"/>
            @if ($lit)
                <text x="{{ $x + $w / 2 }}" y="{{ $y + 36 }}" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="900" fill="#232e5f">{{ $label }}</text>
            @endif
            @php($x += $w + $gap)
        @endforeach
    @endforeach
</svg>

<div class="kb-card kb-card--delivery"><b>PHIẾU GIAO HÀNG</b><i></i><i></i></div>
<div class="kb-card kb-card--order"><b>ĐƠN HÀNG</b><i></i><i></i></div>
