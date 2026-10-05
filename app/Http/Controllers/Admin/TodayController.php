<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CoinLedger;
use App\Services\TodayOverview;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function __invoke(TodayOverview $overview, CoinLedger $ledger): View
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();
        $overview = $overview->build($today, $settings);
        $coins = $ledger->summary($settings, $today->year);
        $dayLabel = $settings->pickupToday($today);

        return view('admin.today', compact('today', 'settings', 'overview', 'coins', 'dayLabel'));
    }
}
