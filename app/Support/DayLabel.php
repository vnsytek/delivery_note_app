<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Nhãn nhóm theo ngày: "Hôm nay, thứ Sáu 06/11/2026", "Hôm qua, ...", "Thứ Hai 03/11/2026" (giờ Việt Nam). */
final class DayLabel
{
    private const WEEKDAYS = ['Chủ nhật', 'thứ Hai', 'thứ Ba', 'thứ Tư', 'thứ Năm', 'thứ Sáu', 'thứ Bảy'];

    public static function for(string $date): string
    {
        $today = now(config('delivery_note.timezone'))->startOfDay();
        $day = Carbon::parse($date);
        $weekday = self::WEEKDAYS[$day->dayOfWeek].' '.$day->format('d/m/Y');

        return match ($date) {
            $today->toDateString() => 'Hôm nay, '.$weekday,
            $today->copy()->subDay()->toDateString() => 'Hôm qua, '.$weekday,
            default => Str::ucfirst($weekday),
        };
    }
}
