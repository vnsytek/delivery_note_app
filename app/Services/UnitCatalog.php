<?php

namespace App\Services;

use App\Models\UnitAlias;
use App\Support\SearchText;

/** Chuẩn hóa đơn vị tính ghi tự do (chiếc, CÁI, 个...) về danh mục đơn vị chuẩn. */
class UnitCatalog
{
    /** @var array<string, string>|null khóa đã chuẩn hóa => tên đơn vị chuẩn */
    private ?array $map = null;

    /** @return array{name: string, known: bool} */
    public function resolve(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return ['name' => '', 'known' => true];
        }
        $name = $this->map()[$this->key($raw)] ?? null;

        return ['name' => $name ?? $raw, 'known' => $name !== null || $raw === ''];
    }

    private function map(): array
    {
        return $this->map ??= UnitAlias::with('unit')->get()
            ->mapWithKeys(fn (UnitAlias $a) => [$this->key($a->alias) => $a->unit->name])
            ->all();
    }

    /** Bỏ ký tự không phải chữ/số, bỏ dấu, chữ thường: "个 / CÁI" và "cái" cùng một khóa nếu đã khai báo alias. */
    private function key(string $value): string
    {
        $letters = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value));

        // Bỏ dấu chữ Latin nhưng giữ nguyên chữ Hán (Str::ascii sẽ làm mất chúng).
        $folded = preg_replace_callback('/./us', function (array $m) {
            $ascii = SearchText::normalize($m[0]);

            return $ascii !== '' ? $ascii : $m[0];
        }, $letters);

        return trim(preg_replace('/\s+/', ' ', $folded));
    }
}
