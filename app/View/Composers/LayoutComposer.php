<?php

namespace App\View\Composers;

use App\Models\Setting;
use App\Models\Staircase;
use App\Support\Fmt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Liefert Kopf und Datumsleiste („Dateline“) für alle Seiten.
 */
class LayoutComposer
{
    public function compose(View $view): void
    {
        // Seite und Layout teilen sich die Daten – nur einmal pro Request berechnen.
        $layout = request()->attributes->get('sauna.layout') ?? $this->build();
        request()->attributes->set('sauna.layout', $layout);

        $view->with(compact('layout'));
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();
        $saunas = Staircase::query()->withSauna()->pluck('number')->all();
        $user = Auth::user();

        $nav = [];
        if ($user) {
            $nav = $user->isAdmin()
                ? [
                    ['route' => 'admin.today', 'label' => 'Heute', 'active' => 'admin.today'],
                    ['route' => 'admin.calendar', 'label' => 'Kalender', 'active' => 'admin.calendar'],
                    ['route' => 'admin.staircases.show', 'label' => 'Stiegen & Parteien', 'active' => 'admin.staircases.*'],
                    ['route' => 'admin.coins', 'label' => 'Münzen', 'active' => 'admin.coins'],
                ]
                : [
                    ['route' => 'user.dashboard', 'label' => 'Saunagänge', 'active' => 'user.dashboard'],
                    ['route' => 'user.reservations.create', 'label' => 'Reservieren', 'active' => 'user.reservations.create|user.reservations.edit'],
                    ['route' => 'user.diary', 'label' => 'Tagebuch', 'active' => 'user.diary'],
                    ['route' => 'user.oils.index', 'label' => 'Öle', 'active' => 'user.oils.*'],
                    ['route' => 'user.coins.index', 'label' => 'Münzen', 'active' => 'user.coins.*'],
                    ['route' => 'user.report', 'label' => 'Jahresbilanz', 'active' => 'user.report'],
                ];
        }

        return [
            'today' => Fmt::long($today),
            'mid' => $user?->isAdmin()
                ? $settings->pickupToday($today)
                : ($user ? $user->name.' · Stiege '.$user->staircase_number.', Top '.$user->apartment : ''),
            'right' => count($saunas).' Saunen · Stiege '.implode(', ', $saunas),
            'roleLabel' => $user?->role->label() ?? '',
            'nav' => $nav,
            'hours' => [
                'monThu' => $settings->monThuLabel(),
                'fri' => $settings->friLabel(),
                'friUntil' => $settings->fri_until,
                'coin' => $settings->coinLabel(),
            ],
        ];
    }
}
