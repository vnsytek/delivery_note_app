<?php

namespace Tests\Feature;

use App\Models\AccentWord;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\User;
use App\Services\AccentDictionary;
use App\Services\AccentSuggester;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccentDictionaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Danh sách từ nhỏ cố định để kết quả đoán không phụ thuộc file dữ liệu thật.
        config([
            'accent.vocabulary' => base_path('tests/fixtures/vi-words.txt'),
            'accent.domain' => base_path('tests/fixtures/vi-domain.txt'),
        ]);
    }

    private function dictionary(): AccentDictionary
    {
        return app(AccentDictionary::class);
    }

    private function word(string $broken): ?AccentWord
    {
        return AccentWord::where('broken', $broken)->first();
    }

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_suggester_guesses_words_and_context_phrases(): void
    {
        Customer::factory()->create(['ma003' => 'x? Minh Tân']);
        $note = DeliveryNote::factory()->create(['erp_address' => 'Thôn 1, x? Minh Tân']);
        foreach (['M?c in Brother', 'C?c pin', 'Gi?y in MàU ??'] as $i => $name) {
            DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => sprintf('%03d', $i + 1), 'erp_name' => $name]);
        }

        $result = app(AccentSuggester::class)->run();

        // "x?" nằm trong địa chỉ: "xã" (từ địa chỉ) thắng "xử" dù "xử" hay gặp hơn trong danh sách từ.
        $this->assertSame(['XÃ', AccentWord::SUGGESTED], [$this->word('X?')->fixed, $this->word('X?')->status]);
        $this->assertSame(['MỰC', AccentWord::SUGGESTED], [$this->word('M?C')->fixed, $this->word('M?C')->status]);
        // "cực" / "cục" ngang nhau: chưa chắc, không tự áp dụng.
        $this->assertSame(AccentWord::UNSURE, $this->word('C?C')->status);
        $this->assertEqualsCanonicalizing(['CỰC', 'CỤC'], array_column($this->word('C?C')->candidates, 'word'));
        // "??" một mình quá mơ hồ; theo ngữ cảnh "MÀU ??" thì là "MÀU ĐỎ".
        $this->assertSame(AccentWord::UNSURE, $this->word('??')->status);
        $this->assertSame(['MÀU ĐỎ', AccentWord::SUGGESTED], [$this->word('MÀU ??')->fixed, $this->word('MÀU ??')->status]);
        $this->assertSame(2, $this->word('X?')->occurrences);
        // X?, M?C, C?C, ??, GI?Y; "GI?Y" không có trong danh sách từ nhưng có trong từ điển viết sẵn (config).
        $this->assertSame(5, $result['words']);
        $this->assertSame(['GIẤY'], array_column($this->word('GI?Y')->candidates, 'word'));

        $restorer = $this->dictionary()->restorer();
        $this->assertSame('Thôn 1, xã Minh Tân', $restorer->suggest('Thôn 1, x? Minh Tân'));
        $this->assertSame('Giấy in MÀU ĐỎ', $restorer->suggest('Gi?y in MàU ??'));
        $this->assertSame('C?c pin', $restorer->suggest('C?c pin'));
        $this->assertSame('auto', $restorer->statusOf('M?c in', $restorer->suggest('M?c in')));
    }

    public function test_suggester_keeps_human_choices_and_drops_words_no_longer_seen(): void
    {
        Customer::factory()->create(['ma003' => 'x? Minh Tân']);
        AccentWord::create(['broken' => 'X?', 'fixed' => 'XỬ', 'status' => AccentWord::CONFIRMED, 'source' => 'manual']);
        AccentWord::create(['broken' => 'C?', 'fixed' => 'CŨ', 'status' => AccentWord::SUGGESTED, 'source' => 'auto', 'occurrences' => 3]);

        app(AccentSuggester::class)->run();

        $this->assertSame(['XỬ', AccentWord::CONFIRMED], [$this->word('X?')->fixed, $this->word('X?')->status]);
        $this->assertSame(1, $this->word('X?')->occurrences);
        $this->assertNull($this->word('C?'), 'mục tự đoán không còn xuất hiện thì bị dọn');
    }

    public function test_learns_from_a_manual_fix_with_context_for_short_words(): void
    {
        $learned = $this->dictionary()->learn('L? m?c ?? Brother', 'Lọ mực đổ Brother');

        $this->assertSame(3, $learned);
        $this->assertSame('MỰC', $this->word('M?C')->fixed);
        $this->assertSame('learned', $this->word('M?C')->source);
        // Từ ngắn học kèm từ bên cạnh, để "L?" trong địa chỉ ("L? CN2" = "LÔ") không bị sửa thành "LỌ".
        $this->assertSame('LỌ MỰC', $this->word('L? M?C')->fixed);
        $this->assertSame('MỰC ĐỔ', $this->word('M?C ??')->fixed);
        $this->assertNull($this->word('L?'));

        // Hai cụm gối nhau ("L? M?C", "M?C ??") cùng được áp dụng.
        $this->assertSame('Lọ mực đổ Brother', $this->dictionary()->restorer()->suggest('L? m?c ?? Brother'));
        $this->assertSame('Màu đỏ', (new VietnameseAccentRestorer(['MÀU ??' => 'MÀU ĐỎ']))->suggest('Màu ??'));
        $this->assertSame('L? CN2', $this->dictionary()->restorer()->suggest('L? CN2'));

        // Không đè lên từ người đã xác nhận; số từ khác nhau thì không học.
        $this->word('M?C')->update(['fixed' => 'MỤC', 'status' => AccentWord::CONFIRMED, 'source' => 'manual']);
        $this->dictionary()->learn('M?c in', 'Mực in');
        $this->assertSame('MỤC', $this->word('M?C')->fixed);
        $this->assertSame(0, $this->dictionary()->learn('Vi?t Nam', 'Việt Nam Co'));
        $this->assertSame(0, $this->dictionary()->learn('Vi?t', 'Viet')); // không khớp khuôn
    }

    public function test_fixing_a_customer_name_teaches_the_dictionary(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001', 'ma003' => 'C?NG TY VI?T']);

        $this->putJson('/api/overrides/A001', ['MA003' => 'CÔNG TY VIỆT'])->assertOk();

        $this->assertSame(['CÔNG', 'learned', AccentWord::SUGGESTED], [$this->word('C?NG')->fixed, $this->word('C?NG')->source, $this->word('C?NG')->status]);
        $this->assertSame('VIỆT', $this->word('VI?T')->fixed);
    }

    public function test_page_confirms_ignores_and_resets_words(): void
    {
        $this->get('/accents')->assertRedirect('/login');
        $this->login();
        $word = AccentWord::create([
            'broken' => 'VI?T', 'fixed' => 'VIỆT', 'status' => AccentWord::UNSURE, 'source' => 'auto', 'occurrences' => 5,
            'candidates' => [['word' => 'VIỆT', 'score' => 4.1], ['word' => 'VIẾT', 'score' => 4.0]], 'example' => 'Vi?t Nam',
        ]);

        $this->get('/accents')->assertOk()->assertSee('Từ điển sửa dấu')->assertSee('VIẾT')->assertSee('5 lần', false);

        // Chọn một phương án: áp dụng ngay, chữ được coi là đúng (không còn là "gợi ý").
        $this->from('/accents')->put("/accents/{$word->id}", ['fixed' => 'VIỆT'])->assertRedirect('/accents');
        $this->assertSame(['VIỆT', AccentWord::CONFIRMED, 'manual'], [$word->fresh()->fixed, $word->fresh()->status, $word->fresh()->source]);
        $restorer = $this->dictionary()->restorer();
        $this->assertSame('Việt Nam', $restorer->suggest('Vi?t Nam'));
        $this->assertSame('ok', $restorer->statusOf('Vi?t Nam', 'Việt Nam'));
        $this->get('/accents?tab=confirmed')->assertSee('đã xác nhận');

        // Kiểm tra dữ liệu nhập.
        $this->from('/accents')->put("/accents/{$word->id}", ['fixed' => 'VIỆT NAM'])->assertSessionHasErrors('fixed');
        $this->from('/accents')->put("/accents/{$word->id}", ['fixed' => 'VI?T'])->assertSessionHasErrors('fixed');
        $this->from('/accents')->put("/accents/{$word->id}", ['fixed' => ''])->assertSessionHasErrors('fixed');

        $this->put("/accents/{$word->id}", ['action' => 'ignore']);
        $this->assertSame(AccentWord::IGNORED, $word->fresh()->status);
        $this->assertSame('Vi?t', $this->dictionary()->restorer()->suggest('Vi?t'));

        $this->put("/accents/{$word->id}", ['action' => 'reset']);
        $this->assertSame([AccentWord::UNSURE, 'auto'], [$word->fresh()->status, $word->fresh()->source]);
    }

    public function test_bulk_confirm_of_suggested_words(): void
    {
        $this->login();
        AccentWord::create(['broken' => 'M?C', 'fixed' => 'MỰC', 'status' => AccentWord::SUGGESTED, 'occurrences' => 9]);
        AccentWord::create(['broken' => 'VI?T', 'fixed' => 'VIỆT', 'status' => AccentWord::UNSURE, 'occurrences' => 4]);

        $this->get('/accents?tab=suggested')->assertOk()->assertSee('Xác nhận tất cả 1 từ');
        $this->post('/accents/confirm-suggested')->assertRedirect(route('accents.index', ['tab' => 'confirmed']));

        $this->assertSame(AccentWord::CONFIRMED, $this->word('M?C')->status);
        $this->assertSame(AccentWord::UNSURE, $this->word('VI?T')->status);
    }

    public function test_dictionary_is_rebuilt_every_hour(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())->first(fn (Event $e) => str_contains((string) $e->command, 'accent:suggest'));

        $this->assertNotNull($event, 'thiếu lịch accent:suggest');
        $this->assertSame('0 * * * *', $event->expression);
    }
}
