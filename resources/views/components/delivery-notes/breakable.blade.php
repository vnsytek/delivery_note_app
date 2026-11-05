@props(['text' => ''])
{{-- Cho phép xuống dòng ngay trước dấu "/" (giống cách Excel/ERP ngắt dòng quy cách: ".../VGA" + "/ĐEN"). --}}
{!! str_replace('/', '<wbr>/', e($text)) !!}
