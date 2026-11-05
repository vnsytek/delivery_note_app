<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\VietnameseAccentRestorer;

/** Tạo bản hiển thị đúng dấu cho tên khách hàng: ưu tiên bản sửa tay, sau đó mới tới gợi ý từ từ điển. */
class CustomerNameResolver
{
    public const STATUS_MANUAL = 'manual';

    /** Tên trường ERP => cột trong bảng customers. */
    private const FIELDS = ['MA002' => 'ma002', 'MA003' => 'ma003'];

    public function __construct(private readonly VietnameseAccentRestorer $restorer) {}

    /**
     * @return array{MA001: string, MA002: string, MA003: string, MA002_VI: string, MA003_VI: string, status: array<string, string>}
     */
    public function present(Customer $customer): array
    {
        $row = ['MA001' => $customer->ma001];
        $row['status'] = [];

        foreach (self::FIELDS as $field => $column) {
            $original = rtrim((string) $customer->{$column});
            $manual = trim((string) $customer->{$column.'_manual'});

            $row[$field] = $original;
            if ($manual !== '') {
                $row[$field.'_VI'] = $manual;
                $row['status'][$field] = self::STATUS_MANUAL;

                continue;
            }

            $fixed = $this->restorer->suggest(trim($original));
            $row[$field.'_VI'] = $fixed;
            $row['status'][$field] = $this->restorer->statusOf(trim($original), $fixed);
        }

        return $row;
    }
}
