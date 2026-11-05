@props(['status'])
@php
    $tags = ['manual' => ['đã sửa tay', 'manual'], 'auto' => ['gợi ý', 'auto'], 'partial' => ['còn ?', 'partial']];
@endphp
@isset($tags[$status])
    <span class="tag tag--{{ $tags[$status][1] }}">{{ $tags[$status][0] }}</span>
@endisset
