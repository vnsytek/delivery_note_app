<?php

namespace App\Http\Controllers;

use App\Actions\UpdateDeliveryNote;
use App\Http\Requests\ListDeliveryNotesRequest;
use App\Http\Requests\UpdateDeliveryNoteRequest;
use App\Models\DeliveryNote;
use App\Models\Order;
use App\Services\CustomerNameResolver;
use App\Services\DeliveryNotePresenter;
use App\Support\DayLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function index(ListDeliveryNotesRequest $request, CustomerNameResolver $customers): View
    {
        $filters = $request->validated();
        $q = trim($filters['q'] ?? '');

        $filtered = DeliveryNote::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $escape = fn (string $s) => '%'.addcslashes($s, '%_\\').'%';
                // "V231-2610" => loại phiếu V231 + số bắt đầu bằng 2610; còn lại tìm theo số/mã khách.
                if (preg_match('/^([A-Za-z0-9]{4})\s*-\s*(\d*)$/', $q, $m)) {
                    $w->where('erp_type', strtoupper($m[1]))->where('erp_no', 'like', addcslashes($m[2], '%_\\').'%');
                } else {
                    $w->where('erp_no', 'like', $escape($q))->orWhere('customer_code', 'like', $escape($q));
                }
            }))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('issued_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('issued_on', '<=', $to));

        $notes = (clone $filtered)
            ->with('customer')
            ->withCount('lines')
            ->orderByDesc('issued_on')->orderByDesc('erp_no')
            ->paginate(25)
            ->withQueryString();

        // Số phiếu thật của từng ngày trên trang (một ngày có thể trải sang trang sau).
        $dates = $notes->getCollection()->map(fn (DeliveryNote $n) => $n->issued_on->toDateString())->unique()->values();
        $dayCounts = $dates->isEmpty() ? collect() : (clone $filtered)->toBase()
            ->selectRaw('DATE(issued_on) AS day, COUNT(*) AS n')
            ->whereIn(DB::raw('DATE(issued_on)'), $dates->all())
            ->groupBy(DB::raw('DATE(issued_on)'))
            ->pluck('n', 'day');

        $names = $notes->getCollection()->mapWithKeys(fn (DeliveryNote $n) => [
            $n->id => $n->customer ? $customers->present($n->customer)['MA003_VI'] : (string) $n->erp_customer_name,
        ]);

        return view('delivery-notes.index', [
            'notes' => $notes,
            'names' => $names,
            'filters' => $filters,
            'groups' => $this->groupByDay($notes->getCollection(), $dayCounts),
            'stats' => $this->stats(),
        ]);
    }

    public function show(Request $request, DeliveryNote $deliveryNote, DeliveryNotePresenter $presenter): View
    {
        $note = $presenter->present($deliveryNote);
        $template = $this->templateFor($note['customer_code'], $request->query('template'));

        // Đơn đặt hàng gốc của các dòng phiếu (để mở lại đơn từ phiếu).
        $orders = $deliveryNote->lines
            ->filter(fn ($line) => $line->order_type && $line->order_no)
            ->map(fn ($line) => ['type' => $line->order_type, 'no' => $line->order_no])
            ->unique(fn ($o) => $o['type'].'-'.$o['no'])
            ->map(fn ($o) => [
                'number' => $o['type'].'-'.$o['no'],
                'id' => Order::where('erp_type', $o['type'])->where('erp_no', $o['no'])->value('id'),
            ])
            ->values();

        return view('delivery-notes.show', [
            'note' => $note,
            'orders' => $orders,
            'template' => $template,
            'templates' => config('delivery_note.template_labels'),
            'partialNames' => collect($note['lines'])->where('name_status', 'partial')->count(),
        ]);
    }

    public function update(UpdateDeliveryNoteRequest $request, DeliveryNote $deliveryNote, UpdateDeliveryNote $update): RedirectResponse
    {
        $update($deliveryNote, $request->validated());

        return redirect()->route('delivery-notes.show', $deliveryNote)->with('status', 'Đã lưu phiếu.');
    }

    public function print(Request $request, DeliveryNote $deliveryNote, DeliveryNotePresenter $presenter): View
    {
        $note = $presenter->present($deliveryNote);
        $template = $this->templateFor($note['customer_code'], $request->query('template'));
        $pages = array_chunk($note['lines'], config("delivery_note.templates.{$template}.lines_per_page")) ?: [[]];

        return view('delivery-notes.print', [
            'note' => $note,
            'pages' => $pages,
            'template' => $template,
            'company' => config('delivery_note.company'),
            // Bản xem trước nhúng trong trang phiếu: chỉ hiện tờ đầu, không có thanh công cụ.
            'preview' => $request->boolean('preview'),
        ]);
    }

    /** Mẫu in: ?template=excel|erp nếu có, nếu không thì theo khách, cuối cùng là mẫu mặc định. */
    private function templateFor(string $customerCode, mixed $requested): string
    {
        $known = array_keys(config('delivery_note.templates'));
        if (is_string($requested) && in_array($requested, $known, true)) {
            return $requested;
        }

        return config("delivery_note.customer_templates.{$customerCode}", config('delivery_note.default_template'));
    }

    /**
     * Phiếu trên trang, nhóm theo ngày: "Hôm nay, thứ Năm 06/11/2026", "Hôm qua, ...", "Thứ Hai 03/11/2026".
     *
     * @return Collection<string, array{label: string, notes: Collection, count: int}>
     */
    private function groupByDay(Collection $notes, Collection $dayCounts): Collection
    {
        return $notes->groupBy(fn (DeliveryNote $n) => $n->issued_on->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'label' => DayLabel::for($date),
                'notes' => $rows,
                'count' => (int) ($dayCounts[$date] ?? $rows->count()),
            ]);
    }

    /** @return array{today: int, total: int, since: ?Carbon} */
    private function stats(): array
    {
        $since = DeliveryNote::min('issued_on');

        return [
            'today' => DeliveryNote::whereDate('issued_on', now(config('delivery_note.timezone'))->toDateString())->count(),
            'total' => DeliveryNote::count(),
            'since' => $since ? Carbon::parse($since) : null,
        ];
    }
}
