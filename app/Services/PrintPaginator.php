<?php

namespace App\Services;

/**
 * Chia dòng hàng thành các tờ in A5 theo CHIỀU CAO ƯỚC TÍNH của từng dòng (không chỉ theo số dòng), vì mỗi dòng giờ có
 * thể cao 2-6 hàng chữ (tên Việt + Trung, quy cách Việt + Trung). Ước tính bằng độ rộng chữ trung bình và độ rộng cột
 * khai báo trong config/delivery_note.php (templates.<mẫu>.layout). Là ước tính nên chừa lề an toàn trong budget_mm.
 */
class PrintPaginator
{
    /**
     * @param  list<array<string, mixed>>  $lines  dòng đã qua DeliveryNotePresenter
     * @return list<list<array<string, mixed>>> luôn có ít nhất một tờ
     */
    public function paginate(array $lines, string $template): array
    {
        $maxRows = (int) config("delivery_note.templates.{$template}.lines_per_page");
        $budget = (float) config("delivery_note.templates.{$template}.layout.budget_mm");

        $pages = [];
        $page = [];
        $used = 0.0;
        foreach ($lines as $line) {
            $height = $this->rowHeight($line, $template);
            $full = count($page) >= $maxRows || ($page !== [] && $used + $height > $budget);
            if ($full) {
                $pages[] = $page;
                $page = [];
                $used = 0.0;
            }
            $page[] = $line;
            $used += $height;
        }

        return $page === [] && $pages !== [] ? $pages : [...$pages, $page];
    }

    /** Tổng chiều cao ước tính (mm) của các dòng trên một tờ. */
    public function usedHeight(array $lines, string $template): float
    {
        return array_sum(array_map(fn (array $line) => $this->rowHeight($line, $template), $lines));
    }

    /** Chiều cao ước tính (mm) của một dòng hàng ở mẫu đã cho. */
    public function rowHeight(array $line, string $template): float
    {
        $layout = config("delivery_note.templates.{$template}.layout");
        $tallest = 1;
        foreach ($layout['cells'] as $cell) {
            $tallest = max($tallest, $this->cellLines($line, $cell, $layout));
        }

        return max($layout['min_row_mm'], $tallest * $layout['line_mm'] + $layout['pad_mm']);
    }

    private function cellLines(array $line, array $cell, array $layout): int
    {
        $room = max(1.0, $cell['width'] - 1.4); // trừ lề trong ô
        $lines = 0;
        foreach ($cell['fields'] as $field) {
            foreach (explode("\n", (string) ($line[$field] ?? '')) as $text) {
                $text = trim($text);
                if ($text !== '') {
                    $lines += max(1, (int) ceil($this->textWidth($text, $layout) / $room));
                }
            }
        }

        return $lines;
    }

    private function textWidth(string $text, array $layout): float
    {
        $han = preg_match_all('/\p{Han}/u', $text);

        return $han * $layout['han_mm'] + (mb_strlen($text) - $han) * $layout['latin_mm'];
    }
}
