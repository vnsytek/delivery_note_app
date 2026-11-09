@props(['counts', 'reviewOnly' => false])
<div class="tabs" aria-label="Lọc khách hàng">
    <button type="button" class="tab" data-filter="all" aria-pressed="{{ $reviewOnly ? 'false' : 'true' }}">
        Tất cả <span class="tab__count" id="count-all">{{ $counts['total'] }}</span>
    </button>
    <button type="button" class="tab" data-filter="review" aria-pressed="{{ $reviewOnly ? 'true' : 'false' }}">
        Cần kiểm tra dấu <span class="tab__count" id="count-review">{{ $counts['review'] }}</span>
    </button>
</div>
<div class="toolbar">
    <label class="visually-hidden" for="search">Tìm khách hàng</label>
    <input type="search" id="search" name="q" class="input" maxlength="100" autocomplete="off" spellcheck="false"
           placeholder="Tìm theo mã hoặc tên, gõ có dấu hay không dấu đều được…">
    <span class="toolbar__info" id="info" aria-live="polite"></span>
</div>
