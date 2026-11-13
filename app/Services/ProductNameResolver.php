<?php

namespace App\Services;

use App\Models\ProductName;
use App\Support\ChineseText;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Support\Collection;

/**
 * Tên hàng hiển thị / in của một mã hàng, dùng chung cho phiếu giao, đơn hàng và trang "Tên hàng":
 * bản sửa tay (bảng product_names) nếu có, không thì gợi ý tự động từ tên ERP
 * (tách chữ Hán thành tên tiếng Trung, phần còn lại khôi phục dấu tiếng Việt).
 */
class ProductNameResolver
{
    public function __construct(private readonly VietnameseAccentRestorer $restorer) {}

    /** @return Collection<string, ProductName> mục sửa tay theo mã hàng */
    public function catalog(iterable $itemCodes): Collection
    {
        return ProductName::whereIn('item_code', collect($itemCodes)->unique()->values())->get()->keyBy('item_code');
    }

    /**
     * @return array{name: string, name_status: string, name_suggested: string, name_zh: string, name_zh_auto: string,
     *               erp_name: string, spec: string, spec_status: string, spec_suggested: string, erp_spec: string, latin: string}
     */
    public function present(?string $erpName, ?string $erpSpec, ?ProductName $entry): array
    {
        $original = trim((string) $erpName);
        // Chữ Hán lẫn trong tên ERP được tách ra làm tên tiếng Trung; phần còn lại mới khôi phục dấu tiếng Việt.
        ['latin' => $latin, 'zh' => $autoZh] = ChineseText::split($original);
        $suggested = $this->restorer->suggest($latin);

        $originalSpec = trim((string) $erpSpec);
        $specSuggested = $this->restorer->suggest($originalSpec);

        return [
            'name' => $entry?->name ?? $suggested,
            'name_status' => $entry?->name !== null ? CustomerNameResolver::STATUS_MANUAL : $this->restorer->statusOf($latin, $suggested),
            'name_suggested' => $suggested,
            'name_zh' => $entry?->name_zh ?? $autoZh,
            'name_zh_auto' => $autoZh,
            'erp_name' => $original,
            'latin' => $latin,
            'spec' => $entry?->spec ?? $specSuggested,
            'spec_status' => $entry?->spec !== null ? CustomerNameResolver::STATUS_MANUAL : $this->restorer->statusOf($originalSpec, $specSuggested),
            'spec_suggested' => $specSuggested,
            'erp_spec' => $originalSpec,
        ];
    }
}
