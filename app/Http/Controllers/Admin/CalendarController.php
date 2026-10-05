<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\WeekCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __invoke(Request $request, WeekCalendar $calendar): View
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();

        $week = $request->query('woche');
        $start = is_string($week) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)
            ? CarbonImmutable::createFromFormat('Y-m-d', $week) ?: $today
            : $today;

        $calendar = $calendar->build($start, $today, $settings);
        $parties = User::query()->parties()->orderBy('staircase_number')->orderBy('apartment')->orderBy('last_name')->get();

        return view('admin.calendar', compact('today', 'settings', 'calendar', 'parties'));
    }
}
