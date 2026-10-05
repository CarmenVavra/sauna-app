<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Zerlegt den Freitext „Wirkung in der Sauna“ für die Anzeige:
 * eine Zeile pro Punkt, „• Titel: Beschreibung“ (Aufzählungszeichen und Titel optional).
 */
class EffectText
{
    /**
     * @return list<array{bullet: bool, title: string|null, text: string}>
     */
    public static function points(?string $text): array
    {
        $points = [];
        foreach (preg_split('/\R/u', (string) $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $bullet = (bool) preg_match('/^[•\-–*·]\s*/u', $line, $m);
            if ($bullet) {
                $line = mb_substr($line, mb_strlen($m[0]));
            }

            $title = null;
            $colon = mb_strpos($line, ':');
            if ($colon !== false && $colon > 0 && $colon <= 60) {
                $title = trim(mb_substr($line, 0, $colon));
                $line = trim(mb_substr($line, $colon + 1));
            }

            $points[] = ['bullet' => $bullet, 'title' => $title, 'text' => $line];
        }

        return $points;
    }

    /** Kurzfassung für die Listenzeile: die Titel der Punkte, sonst der Textanfang. */
    public static function summary(?string $text): string
    {
        $points = self::points($text);
        $titles = array_values(array_filter(array_column($points, 'title')));
        if ($titles !== []) {
            return implode(' · ', $titles);
        }

        return Str::limit(implode(' ', array_column($points, 'text')), 110);
    }
}
