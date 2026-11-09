<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng {{ $note['number'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">{{-- chỉ cho thanh công cụ; phiếu dùng font riêng của mẫu --}}
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <link rel="stylesheet" href="{{ asset('css/print-'.$template.'.css') }}">
</head>
<body @class(['is-preview' => $preview ?? false])>
@unless ($preview ?? false)
    <div class="toolbar no-print">
        <button type="button" id="print-button" class="toolbar__print">In phiếu (A5 ngang)</button>
        <span class="toolbar__label">Mẫu in:</span>
        @foreach (array_keys(config('delivery_note.templates')) as $name)
            <a href="{{ route('delivery-notes.print', [$note['id'], 'template' => $name]) }}" @class(['is-current' => $name === $template])
               @if ($name === $template) aria-current="page" @endif>{{ config('delivery_note.template_labels.'.$name, 'Mẫu '.$name) }}</a>
        @endforeach
        <a href="{{ route('delivery-notes.show', $note['id']) }}" class="toolbar__back">Quay lại phiếu</a>
    </div>
@endunless

@foreach ($pages as $index => $lines)
    <x-dynamic-component :component="'delivery-notes.print-'.$template"
                         :note="$note" :lines="$lines" :company="$company"
                         :page="$index + 1" :pages="count($pages)" />
@endforeach

@unless ($preview ?? false)
    <script>
        document.getElementById('print-button').addEventListener('click', () => window.print());
    </script>
@endunless
</body>
</html>
