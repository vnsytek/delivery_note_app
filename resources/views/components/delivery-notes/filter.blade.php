@props(['filters'])
<form method="GET" action="{{ route('delivery-notes.index') }}" class="toolbar toolbar--wrap">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="50" class="input"
           placeholder="Số phiếu (vd V231-2610) hoặc mã khách">
    <label class="toolbar__check">Từ <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input input--inline"></label>
    <label class="toolbar__check">Đến <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input input--inline"></label>
    <button type="submit" class="btn btn--primary">Lọc</button>
</form>
@if ($errors->any())
    <p class="error" role="alert">{{ $errors->first() }}</p>
@endif
