<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng {{ $note['number'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <link rel="stylesheet" href="{{ asset('css/print-'.$template.'.css') }}">
</head>
<body>
<div class="toolbar no-print">
    <button type="button" id="print-button">In phiếu (A5 ngang)</button>
    @foreach (array_keys(config('delivery_note.templates')) as $name)
        <a href="{{ route('delivery-notes.print', [$note['id'], 'template' => $name]) }}" @class(['is-current' => $name === $template])>Mẫu {{ $name }}</a>
    @endforeach
    <a href="{{ route('delivery-notes.show', $note['id']) }}">Quay lại</a>
</div>

@foreach ($pages as $index => $lines)
    <x-dynamic-component :component="'delivery-notes.print-'.$template"
                         :note="$note" :lines="$lines" :company="$company"
                         :page="$index + 1" :pages="count($pages)" />
@endforeach

<script>
    document.getElementById('print-button').addEventListener('click', () => window.print());
</script>
</body>
</html>
