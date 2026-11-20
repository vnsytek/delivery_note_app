<?php

namespace App\Services;

use App\Support\SearchText;

/**
 * Cho biết một mặt hàng là loại thiết bị gì (laptop, màn hình, loa, linh kiện...) kèm tên tiếng Trung,
 * theo bảng loại trong config/product_types.php: ưu tiên tiền tố mã hàng, sau đó từ khóa trong tên.
 */
class ProductTypeClassifier
{
    /** @var array<string, array{vi: string, zh: string, codes: list<string>, words: list<string>}> */
    private readonly array $types;

    /** @var array<string, string> tiền tố mã (HOA) => khóa loại, dài nhất trước */
    private array $prefixes = [];

    /** @var array<string, string> khóa loại => biểu thức từ khóa */
    private array $patterns = [];

    public function __construct(?array $types = null)
    {
        $this->types = $types ?? config('product_types');

        foreach ($this->types as $key => $type) {
            foreach ($type['codes'] as $prefix) {
                $this->prefixes[strtoupper($prefix)] = $key;
            }
            if ($type['words'] !== []) {
                $alternatives = implode('|', array_map(fn (string $w) => preg_quote($w, '/'), $type['words']));
                $this->patterns[$key] = '/(?<![a-z0-9])(?:'.$alternatives.')(?![a-z0-9])/';
            }
        }
        uksort($this->prefixes, fn (string $a, string $b) => strlen($b) <=> strlen($a));
    }

    /** @return array{key: string, vi: string, zh: string}|null */
    public function classify(?string $itemCode, ?string $name = null): ?array
    {
        $key = $this->byCode((string) $itemCode) ?? $this->byName((string) $name);

        return $key === null ? null : ['key' => $key, 'vi' => $this->types[$key]['vi'], 'zh' => $this->types[$key]['zh']];
    }

    private function byCode(string $itemCode): ?string
    {
        $code = strtoupper(trim($itemCode));
        foreach ($this->prefixes as $prefix => $key) {
            if (str_starts_with($code, $prefix)) {
                return $key;
            }
        }

        return null;
    }

    private function byName(string $name): ?string
    {
        $haystack = SearchText::normalize($name);
        foreach ($this->patterns as $key => $pattern) {
            if (preg_match($pattern, $haystack) === 1) {
                return $key;
            }
        }

        return null;
    }
}
