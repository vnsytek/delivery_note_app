<?php

namespace App\Services;

use App\Models\DeliveryNoteLine;
use App\Models\OrderLine;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/**
 * Danh sách mã hàng cho trang "Tên hàng": mọi mã đã có trên phiếu giao hoặc đơn hàng (đã đồng bộ về app),
 * kèm tên hiển thị hiện tại. Tên / quy cách ERP lấy theo dòng mới nhất của mã đó.
 *
 * Việc khôi phục dấu chạy trong PHP nên lọc / tìm cũng làm trong bộ nhớ (như trang khách hàng);
 * số mã hàng đủ nhỏ để cách này đơn giản và đủ nhanh.
 */
class ProductCatalog
{
    public const TAB_LOST = 'lost';

    public const TAB_MANUAL = 'manual';

    public const TAB_ALL = 'all';

    public function __construct(private readonly ProductNameResolver $resolver) {}

    /** @return Collection<int, array<string, mixed>> */
    public function rows(): Collection
    {
        $items = [];
        // Đơn hàng trước, phiếu giao sau: tên trên phiếu giao (mới hơn) được ưu tiên.
        foreach ([OrderLine::class => 'orders', DeliveryNoteLine::class => 'notes'] as $model => $counter) {
            foreach ($model::query()->orderBy('id')->toBase()->cursor(['item_code', 'erp_name', 'erp_spec']) as $line) {
                $code = (string) $line->item_code;
                $items[$code] ??= ['item_code' => $code, 'notes' => 0, 'orders' => 0];
                $items[$code]['erp_name'] = $line->erp_name;
                $items[$code]['erp_spec'] = $line->erp_spec;
                $items[$code][$counter]++;
            }
        }
        $catalog = $this->resolver->catalog(array_keys($items));

        return collect($items)->map(function (array $item) use ($catalog) {
            $entry = $catalog->get($item['item_code']);
            $product = $this->resolver->present($item['erp_name'], $item['erp_spec'], $entry);

            // $product đứng trước: tên / quy cách ERP đã chuẩn hóa (chuỗi, đã trim) thắng giá trị thô (có thể null).
            return $product + $item + [
                'edited' => $entry !== null,
                'lost' => str_contains($product['name'], '?') || str_contains($product['spec'], '?'),
                'anchor' => 'p-'.substr(md5($item['item_code']), 0, 10),
            ];
        })->values();
    }

    /** @return array{lost: int, manual: int, all: int} */
    public function counts(Collection $rows): array
    {
        return [
            self::TAB_LOST => $rows->where('lost', true)->count(),
            self::TAB_MANUAL => $rows->where('edited', true)->count(),
            self::TAB_ALL => $rows->count(),
        ];
    }

    /** Lọc theo tab và từ khóa (không phân biệt hoa/thường, có/không dấu), mã hay dùng trước. */
    public function filter(Collection $rows, string $tab, string $query): Collection
    {
        $needle = SearchText::normalize(trim($query));

        return $rows
            ->when($tab === self::TAB_LOST, fn ($r) => $r->where('lost', true))
            ->when($tab === self::TAB_MANUAL, fn ($r) => $r->where('edited', true))
            ->when($needle !== '', fn ($r) => $r->filter(fn (array $row) => str_contains(SearchText::normalize(implode(' ', [
                $row['item_code'], $row['name'], $row['name_zh'], $row['spec'], $row['erp_name'],
            ])), $needle)))
            ->sortBy([['notes', 'desc'], ['orders', 'desc'], ['item_code', 'asc']])
            ->values();
    }

    /** Tên / quy cách ERP mới nhất của một mã hàng (null = mã chưa có trên phiếu / đơn nào). */
    public function erpData(string $itemCode): ?array
    {
        $line = DeliveryNoteLine::where('item_code', $itemCode)->latest('id')->first(['erp_name', 'erp_spec'])
            ?? OrderLine::where('item_code', $itemCode)->latest('id')->first(['erp_name', 'erp_spec']);

        return $line ? ['erp_name' => $line->erp_name, 'erp_spec' => $line->erp_spec] : null;
    }
}
