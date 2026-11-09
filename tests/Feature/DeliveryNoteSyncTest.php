<?php

namespace Tests\Feature;

use App\Contracts\ErpDeliveryNoteSource;
use App\Models\Customer;
use App\Models\DeliveryNote;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNoteSyncTest extends TestCase
{
    use RefreshDatabase;

    private function fakeErp(array $notes): void
    {
        $this->app->bind(ErpDeliveryNoteSource::class, fn () => new class($notes) implements ErpDeliveryNoteSource
        {
            public function __construct(private array $notes) {}

            public function chunks(string $since, int $size): iterable
            {
                yield from array_chunk($this->notes, $size);
            }
        });
    }

    private function erpNote(array $override = []): array
    {
        return array_merge([
            'type' => 'V231', 'no' => '261028005', 'date' => '20261103', 'customer_code' => 'UDE',
            'customer_name' => ' CÔNG TY UDE ', 'address' => 'Hải Phòng',
            'lines' => [
                ['seq' => '0001', 'item_code' => 'CPU1', 'name' => 'Core i7', 'spec' => null, 'quantity' => '3.0000',
                    'unit' => 'set', 'customer_po' => '33V2-1', 'customer_item_code' => 'VR-1'],
                ['seq' => '0002', 'item_code' => 'CPU2', 'name' => 'Core i5', 'spec' => '', 'quantity' => 10,
                    'unit' => 'set', 'customer_po' => null, 'customer_item_code' => null],
            ],
        ], $override);
    }

    public function test_sync_creates_notes_with_lines(): void
    {
        Customer::factory()->create(['ma001' => 'UDE']);
        $this->fakeErp([$this->erpNote()]);

        $this->artisan('delivery-notes:sync-erp', ['--since' => '20260101'])
            ->expectsOutputToContain('Đã đồng bộ 1 phiếu')->assertSuccessful();

        $note = DeliveryNote::firstOrFail();
        $this->assertSame('V231-261028005', $note->number());
        $this->assertSame('2026-11-03', $note->issued_on->toDateString());
        $this->assertSame('CÔNG TY UDE', $note->erp_customer_name);
        $this->assertCount(2, $note->lines);
        $this->assertSame('VR-1', $note->lines[0]->customer_item_code);
        $this->assertSame('3.000', $note->lines[0]->quantity);
    }

    public function test_resync_is_idempotent_and_keeps_manual_fields_but_follows_erp_changes(): void
    {
        Customer::factory()->create(['ma001' => 'UDE']);
        $this->fakeErp([$this->erpNote()]);
        $this->artisan('delivery-notes:sync-erp')->assertSuccessful();

        $note = DeliveryNote::firstOrFail();
        $note->update(['receiver' => 'Ms. Hien', 'supplier_code' => 'NL0088']);
        $note->lines()->where('seq', '0001')->update(['dept_code' => 'MVP30']);

        // ERP: sửa số lượng dòng 1, bỏ dòng 2.
        $changed = $this->erpNote();
        $changed['lines'] = [array_merge($changed['lines'][0], ['quantity' => 5])];
        $this->fakeErp([$changed]);
        $this->artisan('delivery-notes:sync-erp')->assertSuccessful();

        $this->assertSame(1, DeliveryNote::count());
        $note->refresh();
        $this->assertSame('Ms. Hien', $note->receiver);
        $this->assertSame('NL0088', $note->supplier_code);
        $this->assertCount(1, $note->lines);
        $this->assertSame('MVP30', $note->lines[0]->dept_code);
        $this->assertSame('5.000', $note->lines[0]->quantity);
    }

    public function test_notes_with_bad_number_or_unknown_customer_are_skipped_and_reported(): void
    {
        Customer::factory()->create(['ma001' => 'UDE']);
        $this->fakeErp([
            $this->erpNote(['no' => '']),               // thiếu số
            $this->erpNote(['no' => '12AB']),            // sai định dạng
            $this->erpNote(['no' => '261028006', 'customer_code' => 'XXX']), // khách chưa có
            $this->erpNote(['no' => '261028007']),       // hợp lệ (V231: 9 chữ số)
            $this->erpNote(['type' => 'V233', 'no' => '2601002']), // hợp lệ (V233/V235: 7 chữ số)
        ]);

        $this->artisan('delivery-notes:sync-erp')
            ->expectsOutputToContain('Đã đồng bộ 2 phiếu')
            ->expectsOutputToContain('Bỏ qua 2 phiếu có số phiếu')
            ->expectsOutputToContain('Bỏ qua 1 phiếu vì khách hàng chưa có')
            ->assertSuccessful();

        $this->assertSame(['2601002', '261028007'], DeliveryNote::orderBy('erp_no')->pluck('erp_no')->all());
    }

    public function test_erp_number_is_unique(): void
    {
        $note = DeliveryNote::factory()->create(['erp_type' => 'V231', 'erp_no' => '261028005']);

        $this->expectException(QueryException::class);
        DeliveryNote::factory()->create(['erp_type' => 'V231', 'erp_no' => '261028005', 'customer_code' => $note->customer_code]);
    }

    public function test_sync_reports_source_failure(): void
    {
        $this->app->bind(ErpDeliveryNoteSource::class, fn () => new class implements ErpDeliveryNoteSource
        {
            public function chunks(string $since, int $size): iterable
            {
                throw new \RuntimeException('không kết nối được ERP');
                yield [];
            }
        });

        $this->artisan('delivery-notes:sync-erp')->expectsOutputToContain('không kết nối được ERP')->assertFailed();
    }
}
