@extends('layouts.app')

@section('title', 'Từ điển sửa dấu')

@php
    $tabs = [
        'unsure' => 'Chờ chọn',
        'suggested' => 'App tự sửa',
        'confirmed' => 'Đã xác nhận',
        'ignored' => 'Bỏ qua',
    ];
    $statusOf = ['unsure' => 'unsure', 'suggested' => 'suggested', 'confirmed' => 'confirmed', 'ignored' => 'ignored'];
    $percent = fn (int $part) => $coverage['total'] > 0 ? (int) floor(100 * $part / $coverage['total']) : 0;
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Từ điển sửa dấu</h1>
            <p class="page-head__sub">
                ERP làm mất các chữ có dấu (hiện thành <mark class="lost">?</mark>). Chọn cách viết đúng cho mỗi từ một lần:
                app tự sửa ở mọi trang và bản in. Không thay đổi gì trong ERP.
            </p>
        </div>
        <div class="page-head__actions">
            <form method="POST" action="{{ route('accents.refresh') }}">
                @csrf
                <button type="submit" class="btn" title="App vẫn tự đoán lại mỗi giờ">Đoán lại ngay</button>
            </form>
        </div>
    </div>

    @if ($coverage['total'] > 0)
        <div class="coverage" aria-label="Mức độ đã sửa">
            <div class="coverage__bar" aria-hidden="true">
                <span class="coverage__part coverage__part--confirmed" style="width: {{ $percent($coverage['confirmed']) }}%"></span>
                <span class="coverage__part coverage__part--auto" style="width: {{ $percent($coverage['fixed'] - $coverage['confirmed']) }}%"></span>
            </div>
            <p class="coverage__text">
                <strong>{{ $percent($coverage['fixed']) }}%</strong> lượt chữ lỗi đang được sửa
                ({{ number_format($coverage['fixed'], 0, ',', '.') }}/{{ number_format($coverage['total'], 0, ',', '.') }}),
                trong đó {{ $percent($coverage['confirmed']) }}% đã có người xác nhận.
            </p>
        </div>
    @endif

    @if ($errors->any())
        <p class="flash flash--error" role="alert">{{ $errors->first() }}</p>
    @endif

    <nav class="tabs" aria-label="Lọc theo trạng thái">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('accents.index', ['tab' => $key]) }}" class="tab"
               @if ($tab === $key) aria-current="page" @endif>
                {{ $label }} <span class="tab__count">{{ $counts[$statusOf[$key]] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    @if ($tab === 'unsure')
        <p class="hint-line">Các từ dưới đây có nhiều cách hiểu ngang nhau nên app chưa tự sửa. Bấm cách viết đúng, hoặc gõ cách khác.</p>
    @elseif ($tab === 'suggested' && ($counts['suggested'] ?? 0) > 0)
        <div class="notice notice--action">
            <p>App đang tự sửa các từ này. Xem lướt, từ nào sai thì bấm chọn lại. Xác nhận để chữ không còn bị đánh dấu “gợi ý”.</p>
            <form method="POST" action="{{ route('accents.confirm-suggested') }}">
                @csrf
                <button type="submit" class="btn btn--primary">Xác nhận tất cả {{ $counts['suggested'] }} từ</button>
            </form>
        </div>
    @endif

    <form method="GET" action="{{ route('accents.index') }}" class="toolbar" role="search">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="visually-hidden" for="accent-q">Tìm từ</label>
        <input type="search" id="accent-q" name="q" value="{{ $q }}" class="input" maxlength="60" autocomplete="off" spellcheck="false"
               placeholder="Tìm từ, vd: vi?t hoặc việt…">
        <span class="toolbar__info">{{ number_format($words->total(), 0, ',', '.') }} mục</span>
    </form>

    @if ($words->isEmpty())
        <p class="empty-state">
            @if ($q !== '')
                Không có từ nào khớp “{{ $q }}”. <a href="{{ route('accents.index', ['tab' => $tab]) }}">Xoá tìm kiếm</a>
            @elseif ($tab === 'unsure')
                Không còn từ nào chờ chọn.
            @else
                Chưa có từ nào ở đây.
            @endif
        </p>
    @else
        <ol class="word-list">
            @foreach ($words as $word)
                <li class="word" id="w-{{ $word->id }}">
                    <div class="word__main">
                        <p class="word__title">
                            <span class="word__broken"><x-lost-marks :text="$word->broken" /></span>
                            <span class="word__arrow" aria-hidden="true">→</span>
                            @if ($word->isApplied())
                                <span class="word__fixed">{{ $word->fixed }}</span>
                            @else
                                <span class="word__fixed word__fixed--none">{{ $word->status === 'ignored' ? 'giữ nguyên ?' : 'chưa sửa' }}</span>
                            @endif
                            @if ($word->status === 'suggested')
                                <span class="chip chip--auto">{{ $word->source === 'learned' ? 'học từ lần sửa tay' : 'gợi ý' }}</span>
                            @elseif ($word->status === 'confirmed')
                                <span class="chip chip--manual">đã xác nhận</span>
                            @endif
                        </p>
                        <p class="word__meta">
                            {{ number_format($word->occurrences, 0, ',', '.') }} lần trong dữ liệu ERP
                            @if ($word->isPhrase())
                                · cụm từ (sửa theo ngữ cảnh)
                            @endif
                        </p>
                        @if ($word->example)
                            <p class="word__example">
                                <span class="word__label">Ví dụ</span> <x-lost-marks :text="$word->example" />
                            </p>
                            @if ($previews[$word->id] !== $word->example)
                                <p class="word__example">
                                    <span class="word__label">Hiện thành</span> <x-lost-marks :text="$previews[$word->id]" />
                                </p>
                            @endif
                        @endif
                    </div>

                    <div class="word__actions">
                        <form method="POST" action="{{ route('accents.update', $word) }}" class="word__choices">
                            @csrf
                            @method('PUT')
                            @foreach (array_slice($word->candidates ?? [], 0, 5) as $candidate)
                                @php
                                    $current = $word->isApplied() && $candidate['word'] === $word->fixed;
                                @endphp
                                <button type="submit" name="fixed" value="{{ $candidate['word'] }}"
                                        @class(['btn', 'btn--sm', 'choice', 'is-current' => $current])
                                        @if ($current) aria-pressed="true" @endif>
                                    {{ $candidate['word'] }}
                                </button>
                            @endforeach
                            @if (in_array($word->status, ['confirmed', 'ignored'], true))
                                <button type="submit" name="action" value="reset" class="link-button">Bỏ {{ $word->status === 'ignored' ? 'bỏ qua' : 'xác nhận' }}</button>
                            @else
                                <button type="submit" name="action" value="ignore" class="link-button"
                                        title="Không phải chữ (vd ký hiệu ® bị mất): giữ nguyên dấu ?">Bỏ qua</button>
                            @endif
                        </form>
                        <form method="POST" action="{{ route('accents.update', $word) }}" class="word__custom">
                            @csrf
                            @method('PUT')
                            <label class="visually-hidden" for="fix-{{ $word->id }}">Cách viết đúng của {{ $word->broken }}</label>
                            <input type="text" id="fix-{{ $word->id }}" name="fixed" class="input input--cell" maxlength="120"
                                   autocomplete="off" spellcheck="false" placeholder="Gõ cách viết khác…">
                            <button type="submit" class="btn btn--sm">Lưu</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="pager">
            <span>{{ $words->firstItem() }}–{{ $words->lastItem() }} trong {{ number_format($words->total(), 0, ',', '.') }} mục</span>
            {{ $words->links('pagination.simple') }}
        </div>
    @endif
@endsection
