<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\TodayOverview;
use App\Services\UsernameGenerator;
use App\Support\Block;
use App\Support\Fmt;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BlockAndFormatTest extends TestCase
{
    private function settings(): Setting
    {
        return new Setting(['mon_thu_until' => '18:00', 'fri_until' => '12:00']);
    }

    public function test_sunday_belongs_to_saturday_block(): void
    {
        $block = Block::fromDay(CarbonImmutable::parse('2026-10-04'));

        $this->assertTrue($block->weekend);
        $this->assertSame('2026-10-03', $block->date->toDateString());
        $this->assertSame('Sa 3. – So 4. Okt', $block->when());
        $this->assertSame('Samstag, 3. – Sonntag, 4. Oktober', $block->whenLong());
        $this->assertSame('bis Fr 2. Okt, 12:00', $block->pickup($this->settings()));
        $this->assertSame('So 4. Okt abends, Postkasten', $block->returnInfo());
    }

    public function test_weekend_across_months(): void
    {
        $block = Block::fromDay(CarbonImmutable::parse('2026-10-31'));

        $this->assertSame('Sa 31. Okt – So 1. Nov', $block->when());
    }

    public function test_weekday_pickup_times(): void
    {
        $this->assertSame('bis Do 1. Okt, 18:00', Block::fromDay(CarbonImmutable::parse('2026-10-01'))->pickup($this->settings()));
        $this->assertSame('bis Fr 2. Okt, 12:00', Block::fromDay(CarbonImmutable::parse('2026-10-02'))->pickup($this->settings()));
    }

    public function test_weekend_is_past_after_friday(): void
    {
        $weekend = Block::fromDay(CarbonImmutable::parse('2026-10-03'));

        $this->assertFalse($weekend->isPast(CarbonImmutable::parse('2026-10-02')));
        $this->assertTrue($weekend->isPast(CarbonImmutable::parse('2026-10-03')));
    }

    public function test_formats(): void
    {
        $this->assertSame('Donnerstag, 1. Oktober 2026', Fmt::long(CarbonImmutable::parse('2026-10-01')));
        $this->assertSame('8. Jän 2026', Fmt::dayYear(CarbonImmutable::parse('2026-01-08')));
        $this->assertSame('1.234,50 €', Fmt::eur(1234.5));
        $this->assertSame('Sieben', Fmt::numberWord(7));
        $this->assertSame('13', Fmt::numberWord(13));
    }

    public function test_username_base(): void
    {
        $this->assertSame('juergen.mueller', (new UsernameGenerator)->base('Jürgen', 'Müller'));
        $this->assertSame('anna.gross', (new UsernameGenerator)->base(' Anna ', 'Groß'));
        $this->assertSame('marielou.dupont', (new UsernameGenerator)->base('Marie-Lou', 'Dupont'));
    }

    public function test_headline(): void
    {
        $overview = new TodayOverview;

        $this->assertSame('Sieben Schlüssel warten auf Abholung. Im Postkasten sind zwei zu prüfen.', $overview->headline(7, 2));
        $this->assertSame('Ein Schlüssel wartet auf Abholung. Im Postkasten ist einer zu prüfen.', $overview->headline(1, 1));
    }
}
