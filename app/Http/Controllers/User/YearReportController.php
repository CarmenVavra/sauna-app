<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PersonalCoins;
use App\Services\YearReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YearReportController extends Controller
{
    public function __invoke(Request $request, YearReport $report, PersonalCoins $personalCoins): View
    {
        $settings = Setting::current();
        $avgPrice = $personalCoins->forUser($request->user(), $settings)['avgPrice'];
        $report = $report->forUser($request->user(), CarbonImmutable::today(), $settings, $avgPrice);

        return view('user.report', compact('report'));
    }
}
