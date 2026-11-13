<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Một từ ERP bị mất dấu và cách sửa của nó (xem App\Services\AccentDictionary). */
class AccentWord extends Model
{
    /** Người dùng đã xác nhận (hoặc app học từ một lần sửa tay): luôn áp dụng. */
    public const CONFIRMED = 'confirmed';

    /** App tự đoán và khá chắc (chỉ một phương án, hoặc vượt trội): áp dụng ngay, nhãn "gợi ý". */
    public const SUGGESTED = 'suggested';

    /** Nhiều phương án ngang nhau: KHÔNG áp dụng, chờ người chọn. */
    public const UNSURE = 'unsure';

    /** Người chọn "bỏ qua": giữ nguyên '?' (vd ký hiệu ® ™ bị mất, không phải chữ). */
    public const IGNORED = 'ignored';

    protected $fillable = ['broken', 'fixed', 'status', 'source', 'candidates', 'occurrences', 'example'];

    protected function casts(): array
    {
        return ['candidates' => 'array', 'occurrences' => 'integer'];
    }

    public function isApplied(): bool
    {
        return in_array($this->status, [self::CONFIRMED, self::SUGGESTED], true) && $this->fixed !== null;
    }

    public function isPhrase(): bool
    {
        return str_contains($this->broken, ' ');
    }

    /** Các mục đang được áp dụng khi hiển thị / in. */
    public function scopeApplied(Builder $query): void
    {
        $query->whereIn('status', [self::CONFIRMED, self::SUGGESTED])->whereNotNull('fixed');
    }
}
