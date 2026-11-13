@props(['text'])
{{-- Hiện chuỗi ERP và tô nổi các ký tự '?' (chữ có dấu bị ERP làm mất). Chuỗi được escape trước khi chèn <mark>. --}}
{!! str_replace('?', '<mark class="lost">?</mark>', e((string) $text)) !!}