<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\ProductName;
use App\Support\VietnameseAccentRestorer;

/** Dựng dữ liệu hiển thị/in của một phiếu: tên đúng dấu, ĐVT chuẩn. */
class DeliveryNotePresenter
{
    public function __construct(
        private readonly CustomerNameResolver $customers,
        private readonly VietnameseAccentRestorer $restorer,
        private readonly UnitCatalog $units,
    ) {}

    public function present(DeliveryNote $note): array
    {
        $note->loadMissing('customer', 'lines');
        $catalog = ProductName::whereIn('item_code', $note->lines->pluck('item_code'))->pluck('name', 'item_code');

        return [
            'id' => $note->id,
            'erp_type' => $note->erp_type,
            'erp_no' => $note->erp_no,
            'number' => $note->number(),
            'type_label' => config('delivery_note.type_labels.'.$note->erp_type, 'Phiếu '.$note->erp_type),
            'date' => $note->issued_on->format('d/m/Y'),
            'date_iso' => $note->issued_on->format('Y-m-d'),
            'customer_code' => $note->customer_code,
            ...$this->customerNames($note),
            'address' => $note->address_manual ?: $this->restorer->suggest($note->erp_address),
            'address_manual' => $note->address_manual,
            'erp_address' => $this->restorer->suggest($note->erp_address),
            'receiver' => $note->receiver,
            'supplier_code' => $note->supplier_code,
            'note' => $note->note,
            'lines' => $note->lines->map(fn (DeliveryNoteLine $l) => $this->line($l, $catalog[$l->item_code] ?? null))->all(),
        ];
    }

    private function customerNames(DeliveryNote $note): array
    {
        if ($note->customer === null) {
            return ['customer_short' => '', 'customer_full' => (string) $note->erp_customer_name];
        }
        $names = $this->customers->present($note->customer);

        return ['customer_short' => $names['MA002_VI'], 'customer_full' => $names['MA003_VI']];
    }

    private function line(DeliveryNoteLine $line, ?string $catalogName): array
    {
        $original = trim((string) $line->erp_name);
        if ($catalogName !== null) {
            $name = $catalogName;
            $status = CustomerNameResolver::STATUS_MANUAL;
        } else {
            $name = $this->restorer->suggest($original);
            $status = $this->restorer->statusOf($original, $name);
        }
        $unit = $this->units->resolve($line->unit_raw);

        return [
            'id' => $line->id,
            'seq' => $line->seq,
            'item_code' => $line->item_code,
            'name' => $name,
            'name_status' => $status,
            'erp_name' => $original,
            'spec' => $this->restorer->suggest($line->erp_spec),
            'quantity' => rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.'),
            'unit' => $unit['name'],
            'unit_known' => $unit['known'],
            'customer_po' => $line->customer_po,
            'customer_item_code' => $line->customer_item_code,
            'dept_code' => $line->dept_code,
            'remark' => $line->remark,
        ];
    }
}
