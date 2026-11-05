<?php

namespace App\Http\Controllers;

use App\Actions\UpdateDeliveryNote;
use App\Http\Requests\ListDeliveryNotesRequest;
use App\Http\Requests\UpdateDeliveryNoteRequest;
use App\Models\DeliveryNote;
use App\Services\CustomerNameResolver;
use App\Services\DeliveryNotePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function index(ListDeliveryNotesRequest $request, CustomerNameResolver $customers): View
    {
        $filters = $request->validated();
        $q = trim($filters['q'] ?? '');

        $notes = DeliveryNote::query()
            ->with('customer')
            ->withCount('lines')
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
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('issued_on', '<=', $to))
            ->orderByDesc('issued_on')->orderByDesc('erp_no')
            ->paginate(25)
            ->withQueryString();

        $names = $notes->getCollection()->mapWithKeys(fn (DeliveryNote $n) => [
            $n->id => $n->customer ? $customers->present($n->customer)['MA003_VI'] : (string) $n->erp_customer_name,
        ]);

        return view('delivery-notes.index', compact('notes', 'names', 'filters'));
    }

    public function show(DeliveryNote $deliveryNote, DeliveryNotePresenter $presenter): View
    {
        return view('delivery-notes.show', ['note' => $presenter->present($deliveryNote)]);
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
        ]);
    }

    /** Mẫu in: ?template=excel|erp nếu có, nếu không thì theo khách, cuối cùng là mẫu mặc định. */
    private function templateFor(string $customerCode, ?string $requested): string
    {
        $known = array_keys(config('delivery_note.templates'));
        if ($requested !== null && in_array($requested, $known, true)) {
            return $requested;
        }

        return config("delivery_note.customer_templates.{$customerCode}", config('delivery_note.default_template'));
    }
}
