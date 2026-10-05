<?php

namespace App\Http\Controllers\User;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveReservationRequest;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\PersonalCoins;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(Request $request, BookingService $booking, PersonalCoins $personalCoins): View
    {
        return $this->form($request, $booking, $personalCoins, null);
    }

    public function edit(Request $request, Reservation $reservation, BookingService $booking, PersonalCoins $personalCoins): View
    {
        Gate::authorize('update', $reservation);

        return $this->form($request, $booking, $personalCoins, $reservation);
    }

    public function store(SaveReservationRequest $request, BookingService $booking): RedirectResponse
    {
        return $this->save($request, $booking, null);
    }

    public function update(SaveReservationRequest $request, Reservation $reservation, BookingService $booking): RedirectResponse
    {
        return $this->save($request, $booking, $reservation);
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('update', $reservation);
        $reservation->moveTo(ReservationStatus::Cancelled);

        return redirect()->route('user.dashboard')->with('toast', 'Abgesagt: '.$reservation->when().'.');
    }

    public function reportKey(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('reportKey', $reservation);
        $reservation->moveTo(ReservationStatus::Reported);

        return redirect()->route('user.dashboard')->with('toast', 'Danke! Der Hauswart prüft den Postkasten.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('delete', $reservation);
        $reservation->delete();

        return redirect()->route('user.dashboard')->with('toast', 'Abgesagte Reservierung '.$reservation->when().' entfernt.');
    }

    private function form(Request $request, BookingService $booking, PersonalCoins $personalCoins, ?Reservation $reservation): View
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();
        $availability = $booking->availability($request->user(), $reservation, $today, $settings);
        $coins = $personalCoins->forUser($request->user(), $settings);

        $selection = [
            'sauna' => (int) (old('staircase_number') ?? $reservation->staircase_number ?? $availability['saunas'][0]['number'] ?? 0),
            'date' => old('date', $reservation?->date->toDateString()),
            'month' => ($reservation->date ?? $today)->format('Y-m'),
            'note' => old('note', $reservation->note ?? ''),
        ];

        return view('user.reservation-form', compact('reservation', 'availability', 'selection', 'coins', 'settings'));
    }

    private function save(SaveReservationRequest $request, BookingService $booking, ?Reservation $reservation): RedirectResponse
    {
        $saved = $booking->book(
            $request->user(),
            $request->integer('staircase_number'),
            CarbonImmutable::createFromFormat('Y-m-d', (string) $request->validated('date')),
            $request->validated('note'),
            CarbonImmutable::today(),
            $reservation,
        );

        $pickup = $saved->block()->pickup(Setting::current());

        return redirect()->route('user.dashboard')->with('toast',
            ($reservation ? 'Geändert: ' : 'Reserviert: ').$saved->when().', Sauna Stiege '.$saved->staircase_number.'. Schlüssel '.$pickup.' abholen.'
        );
    }
}
