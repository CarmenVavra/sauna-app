<?php

namespace App\Http\Controllers\User;

use App\Actions\SaveDiaryEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveDiaryEntryRequest;
use App\Models\Blend;
use App\Models\Oil;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\PersonalCoins;
use App\Services\SaunaVisits;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DiaryController extends Controller
{
    public function show(Request $request, SaunaVisits $visits, PersonalCoins $personalCoins, ?Reservation $reservation = null): View
    {
        if ($reservation) {
            Gate::authorize('writeDiary', $reservation);
        }

        $user = $request->user();
        $settings = Setting::current();
        $past = $visits->past($user, CarbonImmutable::today());
        $selected = $reservation ? $past->firstWhere('id', $reservation->id) : $past->first();
        $entry = $selected?->diaryEntry;
        $entryCount = $past->filter(fn (Reservation $r) => $r->diaryEntry !== null)->count();
        $oils = $user->oils()->get(['name', 'effect']);
        $oilNames = $oils->pluck('name')->all();
        $blends = $user->blends()->with('ingredients')->get()->map(fn (Blend $b) => $b->toEditorArray())->all();
        $oilEffects = $oils->mapWithKeys(fn (Oil $oil) => [$oil->name => ['summary' => $oil->effectSummary(), 'points' => $oil->effectPoints()]])->all();
        $avgPrice = $personalCoins->forUser($user, $settings)['avgPrice'];

        $draft = [
            'coins' => old('coins') ?? $entry->coins ?? 1,
            'temperature' => old('temperature') ?? $entry->temperature ?? 85,
            'sessions' => old('sessions') ?? $entry->sessions ?? 2,
            'infusion' => (bool) (old('infusion') ?? $entry->infusion ?? false),
            'oils' => old('oils', $entry ? $entry->oils->map(fn ($o) => ['name' => $o->name, 'drops' => $o->drops])->all() : []),
            'dosage' => old('dosage', $entry?->dosage->value ?? 'passend'),
            'oilNote' => old('oil_note', $entry->oil_note ?? ''),
            'note' => old('note', $entry->note ?? ''),
            'avgPrice' => $avgPrice,
            'minutesPerCoin' => $settings->minutes_per_coin,
        ];

        return view('user.diary', compact('past', 'selected', 'entry', 'entryCount', 'oilNames', 'oilEffects', 'blends', 'draft'));
    }

    public function update(SaveDiaryEntryRequest $request, Reservation $reservation, SaveDiaryEntry $save): RedirectResponse
    {
        $save->handle($reservation, $request->validated());

        return redirect()->route('user.diary', $reservation)
            ->with('toast', 'Tagebucheintrag für '.$reservation->when().' gespeichert.');
    }
}
