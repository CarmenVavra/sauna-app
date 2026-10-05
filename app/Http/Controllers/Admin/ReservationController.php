<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordCoinsRequest;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationStatusRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, BookingService $booking): RedirectResponse
    {
        $party = User::query()->findOrFail($request->integer('user_id'));
        $reservation = $booking->book(
            $party,
            $request->integer('staircase_number'),
            CarbonImmutable::createFromFormat('Y-m-d', (string) $request->validated('date')),
            null,
            CarbonImmutable::today(),
            forAdmin: true,
        );

        return back()->with('toast', 'Reserviert für '.$party->name.': '.$reservation->when().'.');
    }

    public function updateStatus(UpdateReservationStatusRequest $request, Reservation $reservation): RedirectResponse
    {
        $reservation->loadMissing('user');
        $from = $reservation->status;
        $to = $request->targetStatus();
        $reservation->moveTo($to);

        $message = match (true) {
            $to === ReservationStatus::Issued && $from === ReservationStatus::Reported => 'Meldung zurückgesetzt – die Partei sieht wieder „Schlüssel offen“.',
            $to === ReservationStatus::Issued => 'Schlüssel Stiege '.$reservation->staircase_number.' an '.$reservation->user->name.' ausgegeben.',
            $to === ReservationStatus::Returned => 'Schlüssel Stiege '.$reservation->staircase_number.' ist zurück. Münzen beim Rundgang zählen.',
            default => 'Reservierung storniert.',
        };

        return back()->with('toast', $message);
    }

    public function updateCoins(RecordCoinsRequest $request, Reservation $reservation): RedirectResponse
    {
        $coins = $request->integer('coins');
        $reservation->recordCoins($coins);

        return back()->with('toast', $coins.' Münzen für Sauna Stiege '.$reservation->staircase_number.', '.$reservation->when().' erfasst.');
    }
}
