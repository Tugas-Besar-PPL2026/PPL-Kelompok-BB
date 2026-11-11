<?php

namespace Tests\Unit;

use App\Support\CandidatePresenter;
use App\Support\IdFormat;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SelectionSupportTest extends TestCase
{
    public function test_duration_is_parsed_into_months(): void
    {
        $this->assertSame(8, IdFormat::durationToMonths('8 bulan'));
        $this->assertSame(14, IdFormat::durationToMonths('1 thn 2 bln'));
        $this->assertSame(12, IdFormat::durationToMonths('1 Tahun'));
        $this->assertSame(18, IdFormat::durationToMonths('1,5 tahun'));
        $this->assertSame(6, IdFormat::durationToMonths('6 months'));
        $this->assertNull(IdFormat::durationToMonths('beberapa waktu'));
        $this->assertNull(IdFormat::durationToMonths(''));
        $this->assertNull(IdFormat::durationToMonths(null));
    }

    public function test_months_are_formatted_for_display(): void
    {
        $this->assertSame('10 bln', IdFormat::months(10));
        $this->assertSame('1 thn', IdFormat::months(12));
        $this->assertSame('1 thn 2 bln', IdFormat::months(14));
    }

    public function test_rating_and_dates_use_indonesian_format(): void
    {
        $this->assertSame('4,7', IdFormat::rating(4.7));
        $this->assertSame('4,0', IdFormat::rating(4));
        // 13.11 UTC = 20.11 WIB
        $date = Carbon::parse('2026-11-04 13:11:00', 'UTC');
        $this->assertSame('4 Nov 2026', IdFormat::date($date));
        $this->assertSame('4 Nov 2026, 20.11', IdFormat::dateTime($date));
        $this->assertSame('-', IdFormat::date(null));
    }

    public function test_initials(): void
    {
        $this->assertSame('NP', CandidatePresenter::initials('Nadia Putri Rahma'));
        $this->assertSame('F', CandidatePresenter::initials('Fajar'));
        $this->assertSame('?', CandidatePresenter::initials(''));
    }

    public function test_only_http_links_are_allowed(): void
    {
        $this->assertSame('https://behance.net/x', CandidatePresenter::externalUrl('behance.net/x'));
        $this->assertSame('http://example.com/a', CandidatePresenter::externalUrl('http://example.com/a'));
        $this->assertNull(CandidatePresenter::externalUrl('javascript:alert(1)'));
        $this->assertNull(CandidatePresenter::externalUrl('data:text/html,<b>x</b>'));
        $this->assertNull(CandidatePresenter::externalUrl(''));
        $this->assertSame('behance.net/x', CandidatePresenter::linkLabel('https://www.behance.net/x/'));
    }
}
