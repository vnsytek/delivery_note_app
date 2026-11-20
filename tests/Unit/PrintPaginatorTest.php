<?php

namespace Tests\Unit;

use App\Services\PrintPaginator;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class PrintPaginatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container;
        $container->instance('config', new Repository(['delivery_note' => ['templates' => ['erp' => [
            'lines_per_page' => 5,
            'layout' => ['budget_mm' => 30, 'line_mm' => 3.3, 'pad_mm' => 0.8, 'min_row_mm' => 7.5, 'latin_mm' => 1.55, 'han_mm' => 2.95,
                'cells' => [['width' => 63.2, 'fields' => ['name', 'name_zh', 'spec', 'spec_zh']]]],
        ]]]]));
        Container::setInstance($container);
    }

    private function line(array $override = []): array
    {
        return array_merge(['name' => 'Chuột', 'name_zh' => null, 'spec' => '', 'spec_zh' => null], $override);
    }

    public function test_short_lines_fill_pages_up_to_the_height_budget(): void
    {
        $pages = (new PrintPaginator)->paginate(array_fill(0, 7, $this->line()), 'erp');

        $this->assertSame([4, 3], array_map('count', $pages)); // 4 dòng x 7,5 mm = 30 mm = đúng budget
    }

    public function test_tall_rows_break_the_page_earlier(): void
    {
        $tall = $this->line(['spec' => str_repeat('x', 110), 'spec_zh' => '很長的規格說明很長的規格說明']); // nhiều hàng chữ
        $this->assertGreaterThan(15, (new PrintPaginator)->rowHeight($tall, 'erp')); // 2 dòng như vậy vượt budget 30 mm

        $pages = (new PrintPaginator)->paginate([$tall, $tall, $tall], 'erp');

        $this->assertSame([1, 1, 1], array_map('count', $pages)); // mỗi dòng cao đã chiếm riêng một tờ
    }

    public function test_always_returns_at_least_one_page_and_never_drops_a_huge_row(): void
    {
        $this->assertSame([[]], (new PrintPaginator)->paginate([], 'erp'));

        $huge = $this->line(['spec' => str_repeat('y', 1500)]);
        $this->assertCount(1, (new PrintPaginator)->paginate([$huge], 'erp')[0]);
    }
}
