<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStaircaseRequest;
use App\Models\Staircase;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaircaseController extends Controller
{
    public function show(UsernameGenerator $usernames, ?Staircase $staircase = null): View
    {
        $staircases = Staircase::query()->withCount('parties')->orderBy('number')->get();
        $staircase ??= $staircases->first();
        abort_if($staircase === null, 404);

        $parties = $staircase->parties()->orderBy('apartment')->orderBy('id')->get()->groupBy('apartment');
        $totals = $staircases->sum('apartments').' Wohnungen · '.$staircases->sum('parties_count').' Parteien · '.$staircases->where('has_sauna', true)->count().' Saunen';
        $takenUsernames = User::withTrashed()->pluck('username')->all();
        $startPassword = $usernames->startPassword();
        $credentials = session('credentials');

        return view('admin.staircases', compact('staircases', 'staircase', 'parties', 'totals', 'takenUsernames', 'startPassword', 'credentials'));
    }

    public function update(UpdateStaircaseRequest $request, Staircase $staircase): RedirectResponse
    {
        $hasSauna = $request->boolean('has_sauna');
        $cancelled = 0;
        if ($staircase->has_sauna && ! $hasSauna) {
            $cancelled = $staircase->cancelFutureReservations();
        }

        $staircase->update([
            'apartments' => $request->integer('apartments'),
            'has_sauna' => $hasSauna,
        ]);

        $toast = 'Stiege '.$staircase->number.' gespeichert.'.($cancelled ? ' '.$cancelled.' Reservierungen storniert.' : '');

        return redirect()->route('admin.staircases.show', $staircase)->with('toast', $toast);
    }
}
