<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\SearchText;
use App\Support\VietnameseAccentRestorer;

class CustomerSearchService
{
    public function __construct(private readonly CustomerNameResolver $resolver) {}

    /**
     * Danh sách khách hàng. $query tìm không phân biệt hoa/thường, có/không dấu, trên mã, tên gốc
     * và tên đã khôi phục. $reviewOnly chỉ lấy dòng cần kiểm tra (gợi ý tự động hoặc còn '?').
     *
     * Việc khôi phục dấu chạy trong PHP nên lọc/tìm kiếm cũng làm trong bộ nhớ (chỉ đọc 5 cột nhỏ);
     * quy mô danh mục khách hàng ERP đủ nhỏ để cách này đơn giản và đủ nhanh.
     *
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    public function search(string $query, bool $reviewOnly, int $limit, int $offset): array
    {
        $needle = SearchText::normalize(trim($query));

        $items = Customer::query()
            ->orderBy('ma001')
            ->get(['id', 'ma001', 'ma002', 'ma003', 'ma002_manual', 'ma003_manual'])
            ->map(fn (Customer $customer) => $this->resolver->present($customer))
            ->when($reviewOnly, fn ($rows) => $rows->filter(fn (array $row) => $this->needsReview($row)))
            ->when($needle !== '', fn ($rows) => $rows->filter(fn (array $row) => $this->matches($row, $needle)))
            ->values();

        return [
            'total' => $items->count(),
            'items' => $items->slice($offset, $limit)->values()->all(),
        ];
    }

    private function needsReview(array $row): bool
    {
        return (bool) array_intersect(
            [VietnameseAccentRestorer::STATUS_AUTO, VietnameseAccentRestorer::STATUS_PARTIAL],
            array_values($row['status']),
        );
    }

    private function matches(array $row, string $needle): bool
    {
        $haystack = SearchText::normalize(implode(' ', [
            $row['MA001'], $row['MA002'], $row['MA003'], $row['MA002_VI'], $row['MA003_VI'],
        ]));

        return str_contains($haystack, $needle);
    }
}
