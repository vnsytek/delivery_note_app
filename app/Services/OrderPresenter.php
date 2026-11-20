<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\ProductName;
use App\Support\VietnameseAccentRestorer;

/** Dựng dữ liệu hiển thị của một đơn đặt hàng: tên khách / tên hàng đúng dấu, ĐVT chuẩn, tiền, tiến độ giao. */
class OrderPresenter
{
    public function __construct(
        private readonly CustomerNameResolver $customers,
        private readonly VietnameseAccentRestorer $restorer,
        private readonly UnitCatalog $units,
        private readonly ProductLineText $text,
    ) {}

    public function present(Order $order): array
    {
        $order->loadMissing('customer', 'lines');
        $catalog = ProductName::whereIn('item_code', $order->lines->pluck('item_code'))->get()->keyBy('item_code');
        $names = $order->customer ? $this->customers->present($order->customer) : null;
        $lines = $order->lines->map(fn (OrderLine $l) => $this->line($l, $catalog->get($l->item_code)))->all();

        return [
            'id' => $order->id,
            'number' => $order->number(),
            'type_label' => config('delivery_note.order_type_labels.'.$order->erp_type, 'Đơn '.$order->erp_type),
            'date' => $order->ordered_on->format('d/m/Y'),
            'customer_code' => $order->customer_code,
            'customer_short' => $names['MA002_VI'] ?? '',
            'customer_full' => $names['MA003_VI'] ?? '',
            'customer_po' => $order->customer_po,
            'tax_included' => $order->pricesIncludeTax(),
            'tax_rate' => rtrim(rtrim(number_format($order->tax_rate * 100, 2, ',', ''), '0'), ','),
            'amount' => $order->amount,
            'tax' => $order->tax,
            'total' => $order->amount + $order->tax,
            'void' => $order->isVoid(),
            'status' => $this->status($order),
            'lines' => $lines,
        ];
    }

    /** Tiến độ giao của cả đơn. tone: done | partial | open | void (tên lớp chip). */
    public function status(Order $order): array
    {
        $lines = $order->relationLoaded('lines') ? $order->lines : $order->lines()->get();

        return match (true) {
            $order->isVoid() => ['text' => 'Đã huỷ', 'tone' => 'neutral'],
            $lines->isNotEmpty() && $lines->every(fn (OrderLine $l) => $l->isDone()) => ['text' => 'Đã giao đủ', 'tone' => 'manual'],
            $lines->contains(fn (OrderLine $l) => $l->delivered_quantity > 0) => ['text' => 'Đang giao', 'tone' => 'auto'],
            default => ['text' => 'Chưa giao', 'tone' => 'neutral'],
        };
    }

    private function line(OrderLine $line, ?ProductName $entry): array
    {
        $text = $this->text->build($line->item_code, $line->erp_name, $line->erp_spec, $entry);
        $unit = $this->units->resolve($line->unit_raw);

        return [
            'seq' => $line->seq,
            'item_code' => $line->item_code,
            'customer_item_code' => $line->customer_item_code,
            'name' => $text['name'],
            'name_zh' => $text['name_zh'],
            'name_zh_is_type' => $text['name_zh_source'] === 'type',
            'type' => $text['type'],
            'spec' => $text['spec'],
            'spec_zh' => $text['spec_zh'],
            'quantity' => $this->qty($line->quantity),
            'delivered' => $this->qty($line->delivered_quantity),
            'remaining' => $this->qty(max(0, $line->quantity - $line->delivered_quantity)),
            'done' => $line->isDone(),
            'unit' => $unit['name'],
            'due' => $line->due_on?->format('d/m/Y'),
            'unit_price' => $line->unit_price,
            'amount' => $line->amount,
        ];
    }

    private function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
