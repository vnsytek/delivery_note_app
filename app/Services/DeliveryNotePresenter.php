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
        private readonly ProductLineText $text,
    ) {}

    public function present(DeliveryNote $note): array
    {
        $note->loadMissing('customer', 'lines');
        $catalog = ProductName::whereIn('item_code', $note->lines->pluck('item_code'))->get()->keyBy('item_code');

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
            'lines' => $note->lines->map(fn (DeliveryNoteLine $l) => $this->line($l, $catalog->get($l->item_code)))->all(),
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

    private function line(DeliveryNoteLine $line, ?ProductName $entry): array
    {
        $text = $this->text->build($line->item_code, $line->erp_name, $line->erp_spec, $entry);
        $unit = $this->units->resolve($line->unit_raw);

        return [
            'id' => $line->id,
            'seq' => $line->seq,
            'item_code' => $line->item_code,
            'name' => $text['name'],
            'name_status' => $text['name_status'],
            'name_zh' => $text['name_zh'],
            'name_zh_auto' => $text['name_zh_auto'],
            'name_suggested' => $text['name_suggested'],
            'spec' => $text['spec'],
            'spec_zh' => $text['spec_zh'],
            'spec_suggested' => $text['spec_suggested'],
            'spec_zh_auto' => $text['spec_zh_auto'],
            'type' => $text['type'],
            'erp_name' => trim((string) $line->erp_name),
            'quantity' => $this->qty($line->printedQuantity()),
            'quantity_erp' => $this->qty((float) $line->quantity),
            'quantity_edited' => $line->quantity_manual !== null,
            'unit' => $unit['name'],
            'unit_known' => $unit['known'],
            'customer_po' => $line->customer_po,
            'customer_item_code' => $line->customer_item_code,
            'dept_code' => $line->dept_code,
            'remark' => $line->remark,
        ];
    }

    /** 3.000 -> "3", 1.500 -> "1.5" */
    private function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
