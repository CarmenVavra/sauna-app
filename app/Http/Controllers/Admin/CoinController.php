<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoinSaleRequest;
use App\Models\CoinSale;
use App\Models\Setting;
use App\Models\User;
use App\Services\CoinLedger;
use App\Support\Fmt;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CoinController extends Controller
{
    public function index(CoinLedger $ledger): View
    {
        $today = CarbonImmutable::today();
        $settings = Setting::current();
        $coins = $ledger->summary($settings, $today->year);
        $sales = $ledger->recentSales();
        $salesCount = CoinSale::query()->whereYear('sold_on', $today->year)->count();
        $balances = $ledger->balances();
        $parties = User::query()->parties()->orderBy('staircase_number')->orderBy('apartment')->orderBy('last_name')->get();

        return view('admin.coins', compact('today', 'settings', 'coins', 'sales', 'salesCount', 'balances', 'parties'));
    }

    public function storeSale(StoreCoinSaleRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $party = User::query()->findOrFail($request->integer('user_id'));
        $quantity = $request->integer('quantity');

        $party->coinSales()->create([
            'sold_on' => $request->validated('sold_on') ?? CarbonImmutable::today(),
            'quantity' => $quantity,
            'unit_price' => $settings->coin_price,
        ]);

        return redirect()->route('admin.coins')
            ->with('toast', $quantity.' Münzen an '.$party->name.' verkauft · '.Fmt::eur($quantity * $settings->coin_price).'.');
    }

    public function destroySale(CoinSale $coinSale): RedirectResponse
    {
        $coinSale->load('user');
        $coinSale->delete();

        return redirect()->route('admin.coins')
            ->with('toast', 'Verkauf gelöscht: '.$coinSale->quantity.' Münzen an '.$coinSale->user->name.' vom '.Fmt::dayYear($coinSale->sold_on).'.');
    }
}
