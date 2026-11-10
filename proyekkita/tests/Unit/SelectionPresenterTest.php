<?php

namespace Tests\Unit;

use App\Models\Experience;
use App\Support\SelectionPresenter as P;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SelectionPresenterTest extends TestCase
{
    public function test_initials_use_first_letters_of_first_two_words(): void
    {
        $this->assertSame('NP', P::initials('Nadia Putri Rahma'));
        $this->assertSame('SN', P::initials('  salsabila   nur '));
        $this->assertSame('D', P::initials('Dimas'));
        $this->assertSame('', P::initials(null));
    }

    #[DataProvider('durations')]
    public function test_parse_months(?string $text, ?float $expected): void
    {
        $actual = P::parseMonths($text);

        $expected === null ? $this->assertNull($actual) : $this->assertEqualsWithDelta($expected, $actual, 0.01);
    }

    public static function durations(): array
    {
        return [
            'bulan' => ['6 bulan', 6.0],
            'singkatan bln' => ['8 bln', 8.0],
            'tahun' => ['1 tahun', 12.0],
            'singkatan thn' => ['1 thn', 12.0],
            'gabungan' => ['1 tahun 2 bulan', 14.0],
            'pecahan' => ['1,5 tahun', 18.0],
            'huruf besar' => ['3 Bulan', 3.0],
            'minggu' => ['4 minggu', 4 / 4.345],
            'tanpa satuan' => ['6', null],
            'teks bebas' => ['beberapa waktu', null],
            'kosong' => ['', null],
            'null' => [null, null],
        ];
    }

    public function test_months_label(): void
    {
        $this->assertSame('1 thn 2 bln', P::monthsLabel(14));
        $this->assertSame('10 bln', P::monthsLabel(10));
        $this->assertSame('1 thn', P::monthsLabel(12));
        $this->assertSame('2 thn', P::monthsLabel(24));
        $this->assertSame('< 1 bln', P::monthsLabel(0.3));
        $this->assertNull(P::monthsLabel(0));
    }

    public function test_experience_summary_sums_readable_durations_and_ignores_unreadable(): void
    {
        $experiences = collect([
            new Experience(['duration' => '8 bulan']),
            new Experience(['duration' => '6 bulan']),
            new Experience(['duration' => 'tidak jelas']),
        ]);

        $this->assertSame(['count' => 3, 'duration' => '1 thn 2 bln'], P::experienceSummary($experiences));
        $this->assertSame(['count' => 0, 'duration' => null], P::experienceSummary(collect()));
    }

    public function test_indonesian_dates(): void
    {
        $date = Carbon::parse('2026-08-05 09:07:00');

        $this->assertSame('5 Agt 2026', P::date($date));
        $this->assertSame('5 Agt 2026, 09.07', P::dateTime($date));
        $this->assertSame('-', P::date(null));
        $this->assertSame('4 Nov 2026, 20.11', P::dateTime(Carbon::parse('2026-11-04 20:11:00')));
    }

    public function test_decimal_and_file_size(): void
    {
        $this->assertSame('4,7', P::decimal(4.7));
        $this->assertSame('4,0', P::decimal(4.0));
        $this->assertSame('4,1 MB', P::fileSize((int) (4.1 * 1024 * 1024)));
        $this->assertSame('1,5 KB', P::fileSize(1536));
        $this->assertSame('193 B', P::fileSize(193));
    }

    #[DataProvider('links')]
    public function test_safe_url(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, P::safeUrl($input));
    }

    public static function links(): array
    {
        return [
            'https tetap' => ['https://behance.net/a/b', 'https://behance.net/a/b'],
            'http tetap' => ['http://example.com', 'http://example.com'],
            'tanpa skema diberi https' => ['behance.net/nadiaputri/sambal', 'https://behance.net/nadiaputri/sambal'],
            'dengan spasi tepi' => ['  https://example.com/x  ', 'https://example.com/x'],
            'javascript ditolak' => ['javascript:alert(1)', null],
            'javascript campur huruf besar' => ['JaVaScRiPt:alert(1)', null],
            'data ditolak' => ['data:text/html,<script>alert(1)</script>', null],
            'file ditolak' => ['file:///etc/passwd', null],
            'ftp ditolak' => ['ftp://example.com/x', null],
            'spasi di tengah ditolak' => ['https://exa mple.com', null],
            'kosong' => ['', null],
            'null' => [null, null],
        ];
    }

    public function test_display_url(): void
    {
        $this->assertSame('behance.net/nadiaputri/sambal', P::displayUrl('https://www.behance.net/nadiaputri/sambal/'));
        $this->assertSame('example.com', P::displayUrl('http://example.com'));
    }
}
