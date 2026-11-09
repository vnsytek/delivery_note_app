<?php

namespace App\Http\Controllers;

use App\Models\AccentWord;
use App\Services\AccentDictionary;
use App\Services\AccentSuggester;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Trang "Từ điển sửa dấu": mỗi từ ERP bị mất dấu chỉ cần chọn đúng một lần, app tự sửa ở mọi trang và bản in.
 * Mọi thay đổi chỉ nằm trong bảng accent_words của app; ERP không bị đụng tới.
 */
class AccentWordController extends Controller
{
    /** Tab => trạng thái hiển thị. */
    private const TABS = [
        'unsure' => [AccentWord::UNSURE],
        'suggested' => [AccentWord::SUGGESTED],
        'confirmed' => [AccentWord::CONFIRMED],
        'ignored' => [AccentWord::IGNORED],
    ];

    public function index(Request $request, VietnameseAccentRestorer $restorer): View
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'unsure';
        $q = trim((string) $request->query('q'));

        $words = AccentWord::query()
            ->whereIn('status', self::TABS[$tab])
            ->when(in_array($tab, ['unsure', 'suggested'], true), fn ($query) => $query->where('occurrences', '>', 0))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('broken', 'like', '%'.mb_strtoupper($q).'%')
                ->orWhere('fixed', 'like', '%'.mb_strtoupper($q).'%')))
            ->orderByDesc('occurrences')->orderBy('broken')
            ->paginate(25)->withQueryString();

        $counts = AccentWord::query()
            ->selectRaw('status, count(*) as n')
            ->where(fn ($w) => $w->where('occurrences', '>', 0)->orWhereIn('status', [AccentWord::CONFIRMED, AccentWord::IGNORED]))
            ->groupBy('status')->pluck('n', 'status');

        // Độ phủ: trong mọi lần một từ lỗi xuất hiện, bao nhiêu lần đã có cách sửa đang áp dụng.
        $single = AccentWord::query()->where('broken', 'not like', '% %')->where('occurrences', '>', 0)
            ->get(['status', 'fixed', 'occurrences']);
        $coverage = [
            'total' => (int) $single->sum('occurrences'),
            'fixed' => (int) $single->filter->isApplied()->sum('occurrences'),
            'confirmed' => (int) $single->where('status', AccentWord::CONFIRMED)->sum('occurrences'),
        ];

        $previews = $words->getCollection()->mapWithKeys(fn (AccentWord $w) => [$w->id => $restorer->suggest($w->example)]);

        return view('accents.index', compact('words', 'counts', 'coverage', 'tab', 'q', 'previews'));
    }

    public function update(Request $request, AccentWord $accentWord, AccentDictionary $dictionary): RedirectResponse
    {
        $action = $request->input('action', 'confirm');

        if ($action === 'ignore') {
            $dictionary->ignore($accentWord);

            return back()->with('status', "Bỏ qua \"{$accentWord->broken}\": giữ nguyên dấu ?.");
        }
        if ($action === 'reset') {
            $dictionary->reset($accentWord);

            return back()->with('status', "Đã trả \"{$accentWord->broken}\" về cho app tự đoán lại.");
        }

        $data = $request->validate(['fixed' => ['required', 'string', 'max:120']], [
            'fixed.required' => 'Chọn hoặc gõ cách viết đúng.',
        ]);
        $fixed = preg_replace('/\s+/u', ' ', trim($data['fixed']));
        if (str_contains($fixed, '?')) {
            throw ValidationException::withMessages(['fixed' => 'Cách viết đúng không được còn dấu ?.']);
        }
        if (count(explode(' ', $fixed)) !== count(explode(' ', $accentWord->broken))) {
            throw ValidationException::withMessages([
                'fixed' => "\"{$accentWord->broken}\" có ".count(explode(' ', $accentWord->broken)).' từ: cách viết đúng phải có cùng số từ.',
            ]);
        }

        $dictionary->confirm($accentWord, $fixed);

        return back()->with('status', "Đã lưu: {$accentWord->broken} → ".mb_strtoupper($fixed).'. App sửa ở mọi nơi có từ này.');
    }

    /** Xác nhận một lượt mọi từ app đang tự sửa (gợi ý) - dùng sau khi đã xem lướt danh sách. */
    public function confirmSuggested(AccentDictionary $dictionary): RedirectResponse
    {
        $n = AccentWord::query()->where('status', AccentWord::SUGGESTED)->whereNotNull('fixed')->where('occurrences', '>', 0)
            ->update(['status' => AccentWord::CONFIRMED, 'source' => 'manual']);
        $dictionary->forget();

        return redirect()->route('accents.index', ['tab' => 'confirmed'])->with('status', "Đã xác nhận {$n} từ.");
    }

    /** Đoán lại ngay (bình thường app tự chạy mỗi giờ). */
    public function refresh(AccentSuggester $suggester): RedirectResponse
    {
        $r = $suggester->run();

        return back()->with('status', "Đã đoán lại: {$r['words']} từ lỗi - {$r['suggested']} app tự sửa, {$r['unsure']} chờ chọn.");
    }
}
