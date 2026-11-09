@props([
    'filters',
    'action' => route('delivery-notes.index'),
    'label' => 'Tìm phiếu',
    'placeholder' => 'Số phiếu (vd V231-2611) hoặc mã khách…',
])
<form method="GET" action="{{ $action }}" class="filters" role="search">
    <div class="field field--grow">
        <label class="field__label" for="filter-q">{{ $label }}</label>
        <input type="search" id="filter-q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="50" class="input"
               autocomplete="off" spellcheck="false" placeholder="{{ $placeholder }}">
    </div>
    <div class="field">
        <label class="field__label" for="filter-from">Từ ngày</label>
        <input type="date" id="filter-from" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
    </div>
    <div class="field">
        <label class="field__label" for="filter-to">Đến ngày</label>
        <input type="date" id="filter-to" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
    </div>
    <button type="submit" class="btn btn--primary">Lọc</button>
    @if (array_filter($filters))
        <a href="{{ $action }}" class="btn">Xoá lọc</a>
    @endif
</form>
@if ($errors->any())
    <p class="error" role="alert">{{ $errors->first() }}</p>
@endif
