<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreCoinPurchaseRequest;
use App\Models\CoinPurchase;
use App\Models\Setting;
use App\Services\PersonalCoins;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CoinPurchaseController extends Controller
{
    public function index(Request $request, PersonalCoins $personalCoins): View
    {
        $settings = Setting::current();
        $coins = $personalCoins->forUser($request->user(), $settings);
        $purchases = $request->user()->coinPurchases()->orderByDesc('purchased_on')->orderByDesc('id')->get();
        $today = CarbonImmutable::today();

        return view('user.coins', compact('coins', 'purchases', 'settings', 'today'));
    }

    public function store(StoreCoinPurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['purchased_on'] ??= CarbonImmutable::today();
        $request->user()->coinPurchases()->create($data);

        return redirect()->route('user.coins.index')->with('toast', $data['quantity'].' Münzen erfasst.');
    }

    public function destroy(CoinPurchase $coinPurchase): RedirectResponse
    {
        Gate::authorize('delete', $coinPurchase);
        $coinPurchase->delete();

        return redirect()->route('user.coins.index')->with('toast', 'Kauf gelöscht.');
    }
}
