<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PersonalCoins;
use App\Services\SaunaVisits;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SaunaVisits $visits, PersonalCoins $personalCoins): View
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();
        $visits = $visits->overview($request->user(), $today);
        $coins = $personalCoins->forUser($request->user(), $settings);

        return view('user.dashboard', compact('today', 'settings', 'visits', 'coins'));
    }
}
