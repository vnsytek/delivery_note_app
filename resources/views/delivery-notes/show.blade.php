@extends('layouts.app')

@section('title', 'Phiếu '.$note['number'])

@section('content')
    @php
        $printUrl = fn (string $name) => route('delivery-notes.print', [$note['id'], 'template' => $name]);
        $previewUrl = fn (string $name) => route('delivery-notes.print', [$note['id'], 'template' => $name, 'preview' => 1]);
    @endphp

    <nav class="crumbs" aria-label="Đường dẫn">
        <a href="{{ route('delivery-notes.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Phiếu giao hàng
        </a>
    </nav>

    <div class="page-head">
        <div>
            <h1 translate="no">{{ $note['number'] }}</h1>
            <p class="page-head__sub">
                {{ $note['type_label'] }} ngày {{ $note['date'] }}, khách
                <span class="code">{{ $note['customer_code'] }}</span>
                {{ $note['customer_full'] ?: $note['customer_short'] }}
                @if ($orders->isNotEmpty())
                    <span class="sub">
                        Lập từ đơn
                        @foreach ($orders as $order)
                            @if ($order['id'])
                                <a href="{{ route('orders.show', $order['id']) }}" translate="no">{{ $order['number'] }}</a>@if (! $loop->last), @endif
                            @else
                                <span translate="no">{{ $order['number'] }}</span>@if (! $loop->last), @endif
                            @endif
                        @endforeach
                    </span>
                @endif
            </p>
        </div>
        <div class="page-head__actions">
            <button type="submit" form="note-form" class="btn">Lưu phiếu</button>
            <a href="{{ $printUrl($template) }}" target="_blank" rel="noopener" class="btn btn--primary" data-print-link>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
                In phiếu A5
            </a>
        </div>
    </div>

    @if ($partialNames)
        <p class="notice" role="note">
            {{ $partialNames }} tên hàng còn ký tự <mark class="lost">?</mark> mà từ điển chưa khôi phục được.
            Sửa trong bảng “Hàng trong phiếu” rồi bấm Lưu trước khi in.
        </p>
    @endif

    <form id="note-form" method="POST" action="{{ route('delivery-notes.update', $note['id']) }}">
        @csrf
        @method('PUT')

        <div class="note-grid">
            <section class="panel" aria-labelledby="h-delivery">
                <h2 id="h-delivery" class="panel__title">Thông tin giao hàng</h2>
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="receiver">Người nhận <span class="zh">收貨人</span></label>
                        <input type="text" id="receiver" name="receiver" maxlength="100" class="input"
                               value="{{ old('receiver', $note['receiver']) }}" autocomplete="off">
                    </div>
                    <div class="field">
                        <label class="field__label" for="supplier_code">Mã nhà cung cấp tại khách <span class="zh">供應商代</span></label>
                        <input type="text" id="supplier_code" name="supplier_code" maxlength="50" class="input"
                               value="{{ old('supplier_code', $note['supplier_code']) }}" autocomplete="off" spellcheck="false">
                    </div>
                    <div class="field field--wide">
                        <label class="field__label" for="address_manual">Địa chỉ giao hàng</label>
                        <textarea id="address_manual" name="address_manual" maxlength="500" rows="2" class="input input--area"
                                  aria-describedby="address-hint" autocomplete="off">{{ old('address_manual', $note['address_manual']) }}</textarea>
                        <span class="field__hint" id="address-hint">
                            @if (trim((string) $note['erp_address']) !== '')
                                Để trống thì in địa chỉ trong ERP: <x-lost-marks :text="$note['erp_address']" />
                            @else
                                ERP không có địa chỉ cho phiếu này.
                            @endif
                        </span>
                    </div>
                    <div class="field field--wide">
                        <label class="field__label" for="note">Ghi chú</label>
                        <textarea id="note" name="note" maxlength="500" rows="2" class="input input--area" autocomplete="off">{{ old('note', $note['note']) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="panel" aria-labelledby="h-preview">
                <div class="panel__head">
                    <h2 id="h-preview" class="panel__title">Bản in A5</h2>
                    <div class="segmented" aria-label="Mẫu in">
                        @foreach ($templates as $name => $label)
                            <a href="{{ route('delivery-notes.show', [$note['id'], 'template' => $name]) }}"
                               data-template="{{ $name }}" data-preview-url="{{ $previewUrl($name) }}" data-print-url="{{ $printUrl($name) }}"
                               aria-current="{{ $name === $template ? 'true' : 'false' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="preview" data-preview>
                    <iframe src="{{ $previewUrl($template) }}" title="Bản xem trước phiếu {{ $note['number'] }}" tabindex="-1" loading="lazy"></iframe>
                </div>
                <p class="panel__note" data-preview-note aria-live="polite">Bản in theo dữ liệu đã lưu{{ count($note['lines']) > config("delivery_note.templates.{$template}.lines_per_page") ? ', đây là tờ đầu tiên' : '' }}.</p>
            </section>
        </div>

        <section aria-labelledby="h-lines">
            <h2 id="h-lines" class="section-title">Hàng trong phiếu<span class="muted">{{ count($note['lines']) }} dòng</span></h2>
            <div class="table-wrap">
                <table class="table table--lines">
                    <thead>
                    <tr>
                        <th scope="col" class="col-seq">STT</th>
                        <th scope="col" class="col-name">Tên hàng (Việt / Trung)</th>
                        <th scope="col" class="col-qty num">Số lượng</th>
                        <th scope="col" class="col-ref">PO, mã hàng của khách</th>
                        <th scope="col" class="col-input">Mã bộ phận</th>
                        <th scope="col" class="col-input">Ghi chú dòng</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($note['lines'] as $line)
                        <tr>
                            <td class="col-seq" data-label="STT">{{ $line['seq'] }}</td>
                            <td class="col-name" data-label="Tên hàng (Việt / Trung)">
                                <div class="name-edit">
                                    <label class="visually-hidden" for="line-name-{{ $line['id'] }}">Tên hàng dòng {{ $line['seq'] }}</label>
                                    <input type="text" id="line-name-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][name]" maxlength="255"
                                           class="input input--cell" value="{{ old('lines.'.$line['id'].'.name', $line['name']) }}" autocomplete="off">
                                    <x-status-tag :status="$line['name_status']" />
                                </div>
                                <label class="visually-hidden" for="line-name-zh-{{ $line['id'] }}">Tên tiếng Trung dòng {{ $line['seq'] }}</label>
                                <input type="text" id="line-name-zh-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][name_zh]" maxlength="255"
                                       class="input input--cell input--zh" placeholder="Tên tiếng Trung (in dưới tên Việt)"
                                       value="{{ old('lines.'.$line['id'].'.name_zh', $line['name_zh']) }}" autocomplete="off">
                                @if ($line['erp_name'] !== '' && $line['erp_name'] !== $line['name'])
                                    <span class="original">Gốc trong ERP: <x-lost-marks :text="$line['erp_name']" /></span>
                                @endif
                                <span class="sub">Mã {{ $line['item_code'] }}@if ($line['spec']), quy cách {{ $line['spec'] }}@endif</span>
                            </td>
                            <td class="col-qty num" data-label="Số lượng">
                                {{ $line['quantity'] }} {{ $line['unit'] }}
                                @unless ($line['unit_known'])<span class="chip chip--auto" title="Đơn vị chưa có trong danh mục ĐVT">lạ</span>@endunless
                            </td>
                            <td class="col-ref" data-label="PO, mã hàng của khách">
                                @if ($line['customer_po'] || $line['customer_item_code'])
                                    @if ($line['customer_po'])<span>PO {{ $line['customer_po'] }}</span>@endif
                                    @if ($line['customer_item_code'])<span class="sub">Mã khách {{ $line['customer_item_code'] }}</span>@endif
                                @else
                                    <span class="dash" aria-label="Không có">—</span>
                                @endif
                            </td>
                            <td class="col-input" data-label="Mã bộ phận">
                                <label class="visually-hidden" for="line-dept-{{ $line['id'] }}">Mã bộ phận dòng {{ $line['seq'] }}</label>
                                <input type="text" id="line-dept-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][dept_code]" maxlength="30"
                                       class="input input--cell" value="{{ old('lines.'.$line['id'].'.dept_code', $line['dept_code']) }}"
                                       autocomplete="off" spellcheck="false">
                            </td>
                            <td class="col-input" data-label="Ghi chú dòng">
                                <label class="visually-hidden" for="line-remark-{{ $line['id'] }}">Ghi chú dòng {{ $line['seq'] }}</label>
                                <input type="text" id="line-remark-{{ $line['id'] }}" name="lines[{{ $line['id'] }}][remark]" maxlength="255"
                                       class="input input--cell" value="{{ old('lines.'.$line['id'].'.remark', $line['remark']) }}" autocomplete="off">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="form-actions">
            <a href="{{ route('delivery-notes.index') }}" class="btn">Quay lại danh sách</a>
            <button type="submit" class="btn btn--primary">Lưu phiếu</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Bản xem trước: thu nhỏ tờ A5 (794 x 559px) vừa khung; đổi mẫu in không cần tải lại trang.
        (function () {
            var box = document.querySelector('[data-preview]');
            if (!box) return;
            var frame = box.querySelector('iframe');
            function fit() {
                var scale = box.clientWidth / 794;
                frame.style.transform = 'scale(' + scale + ')';
                box.style.height = Math.round(559 * scale) + 'px';
            }
            if ('ResizeObserver' in window) new ResizeObserver(fit).observe(box);
            fit();

            document.querySelectorAll('[data-template]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    frame.src = link.dataset.previewUrl;
                    document.querySelectorAll('[data-print-link]').forEach(function (a) { a.href = link.dataset.printUrl; });
                    document.querySelectorAll('[data-template]').forEach(function (other) {
                        other.setAttribute('aria-current', other === link ? 'true' : 'false');
                    });
                    history.replaceState(null, '', link.href);
                });
            });

            // Sửa ô nào thì nhắc: bản in chỉ đổi sau khi lưu; rời trang khi chưa lưu thì trình duyệt hỏi lại.
            var note = document.querySelector('[data-preview-note]');
            var form = document.getElementById('note-form');
            var dirty = false;
            form.addEventListener('input', function () {
                if (!dirty) note.textContent = 'Có thay đổi chưa lưu. Bấm Lưu phiếu để bản in cập nhật.';
                dirty = true;
            });
            form.addEventListener('submit', function () { dirty = false; });
            window.addEventListener('beforeunload', function (event) {
                if (dirty) { event.preventDefault(); event.returnValue = ''; }
            });
        })();
    </script>
@endpush
