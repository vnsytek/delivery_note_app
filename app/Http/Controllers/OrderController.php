<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListDeliveryNotesRequest;
use App\Models\Order;
use App\Services\CustomerNameResolver;
use App\Services\OrderPresenter;
use App\Support\DayLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Đơn đặt hàng từ ERP: tìm lại, xem / chụp gửi khách (bật/tắt giá), mở phiếu giao lập từ đơn. Chỉ đọc. */
class OrderController extends Controller
{
    public function index(ListDeliveryNotesRequest $request, CustomerNameResolver $customers, OrderPresenter $presenter): View
    {
        $filters = $request->validated();
        $q = trim($filters['q'] ?? '');

        $filtered = Order::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $escape = fn (string $s) => '%'.addcslashes($s, '%_\\').'%';
                // "V221-2611" => loại đơn V221 + số bắt đầu bằng 2611; còn lại tìm theo số đơn, mã khách, PO của khách.
                if (preg_match('/^([A-Za-z0-9]{4})\s*-\s*(\d*)$/', $q, $m)) {
                    $w->where('erp_type', strtoupper($m[1]))->where('erp_no', 'like', addcslashes($m[2], '%_\\').'%');
                } else {
                    $w->where('erp_no', 'like', $escape($q))
                        ->orWhere('customer_code', 'like', $escape($q))
                        ->orWhere('customer_po', 'like', $escape($q));
                }
            }))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('ordered_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('ordered_on', '<=', $to));

        $orders = (clone $filtered)
            ->with(['customer', 'lines'])
            ->orderByDesc('ordered_on')->orderByDesc('erp_no')
            ->paginate(25)
            ->withQueryString();

        $dates = $orders->getCollection()->map(fn (Order $o) => $o->ordered_on->toDateString())->unique()->values();
        $dayCounts = $dates->isEmpty() ? collect() : (clone $filtered)->toBase()
            ->selectRaw('DATE(ordered_on) AS day, COUNT(*) AS n')
            ->whereIn(DB::raw('DATE(ordered_on)'), $dates->all())
            ->groupBy(DB::raw('DATE(ordered_on)'))
            ->pluck('n', 'day');

        $rows = $orders->getCollection()->mapWithKeys(fn (Order $o) => [$o->id => [
            'name' => $o->customer ? $customers->present($o->customer)['MA003_VI'] : '',
            'status' => $presenter->status($o),
        ]]);
        $since = Order::min('ordered_on');

        return view('orders.index', [
            'orders' => $orders,
            'rows' => $rows,
            'filters' => $filters,
            'groups' => $this->groupByDay($orders->getCollection(), $dayCounts),
            'stats' => [
                'today' => Order::whereDate('ordered_on', now(config('delivery_note.timezone'))->toDateString())->count(),
                'total' => Order::count(),
                'since' => $since ? Carbon::parse($since) : null,
            ],
        ]);
    }

    public function show(Request $request, Order $order, OrderPresenter $presenter): View
    {
        return view('orders.show', [
            'order' => $presenter->present($order),
            'company' => config('delivery_note.company'),
            'showPrices' => $request->boolean('gia'),
            'deliveryNotes' => $order->deliveryNotes()->get(),
        ]);
    }

    private function groupByDay(Collection $orders, Collection $dayCounts): Collection
    {
        return $orders->groupBy(fn (Order $o) => $o->ordered_on->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'label' => DayLabel::for($date),
                'orders' => $rows,
                'count' => (int) ($dayCounts[$date] ?? $rows->count()),
            ]);
    }
}
