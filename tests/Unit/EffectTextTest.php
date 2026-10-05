<?php

namespace Tests\Unit;

use App\Support\EffectText;
use PHPUnit\Framework\TestCase;

class EffectTextTest extends TestCase
{
    public function test_bullets_with_titles_are_split(): void
    {
        $text = "• Befreiung der Atemwege: Öffnet die Bronchien.\n\n- Belebend: Vertreibt Müdigkeit.\nEinfach ein Satz ohne Titel.";

        $this->assertSame([
            ['bullet' => true, 'title' => 'Befreiung der Atemwege', 'text' => 'Öffnet die Bronchien.'],
            ['bullet' => true, 'title' => 'Belebend', 'text' => 'Vertreibt Müdigkeit.'],
            ['bullet' => false, 'title' => null, 'text' => 'Einfach ein Satz ohne Titel.'],
        ], EffectText::points($text));
        $this->assertSame('Befreiung der Atemwege · Belebend', EffectText::summary($text));
    }

    public function test_late_colon_is_not_a_title(): void
    {
        $text = 'Der Duft ist sehr angenehm und wirkt im heißen Dampf der Sauna besonders intensiv, wichtig: sparsam dosieren.';

        $this->assertNull(EffectText::points($text)[0]['title']);
        $this->assertStringStartsWith('Der Duft ist sehr angenehm', EffectText::summary($text));
    }

    public function test_empty_text(): void
    {
        $this->assertSame([], EffectText::points(null));
        $this->assertSame('', EffectText::summary(''));
    }
}
