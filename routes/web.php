<?php

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CoinController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaircaseController;
use App\Http\Controllers\Admin\TodayController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\BlendController;
use App\Http\Controllers\User\CoinPurchaseController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\DiaryController;
use App\Http\Controllers\User\OilController;
use App\Http\Controllers\User\ReservationController;
use App\Http\Controllers\User\YearReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::post('/login/demo/{role}', DemoLoginController::class)->whereIn('role', ['admin', 'user'])->name('login.demo');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/passwort', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/passwort', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', HomeController::class)->name('home');

        // Hauswart
        Route::middleware('role:admin')->prefix('hauswart')->name('admin.')->group(function () {
            Route::get('/', TodayController::class)->name('today');
            Route::get('/kalender', CalendarController::class)->name('calendar');

            Route::post('/reservierungen', [AdminReservationController::class, 'store'])->name('reservations.store');
            Route::patch('/reservierungen/{reservation}/status', [AdminReservationController::class, 'updateStatus'])->name('reservations.status');
            Route::patch('/reservierungen/{reservation}/muenzen', [AdminReservationController::class, 'updateCoins'])->name('reservations.coins');

            Route::get('/stiegen/{staircase?}', [StaircaseController::class, 'show'])->name('staircases.show');
            Route::patch('/stiegen/{staircase}', [StaircaseController::class, 'update'])->name('staircases.update');
            Route::post('/stiegen/{staircase}/parteien', [PartyController::class, 'store'])->name('parties.store');
            Route::patch('/parteien/{party}', [PartyController::class, 'update'])->name('parties.update');
            Route::delete('/parteien/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');
            Route::post('/parteien/{party}/passwort', [PartyController::class, 'resetPassword'])->name('parties.password');

            Route::get('/muenzen', [CoinController::class, 'index'])->name('coins');
            Route::post('/muenzen/verkaeufe', [CoinController::class, 'storeSale'])->name('coins.sales.store');
            Route::delete('/muenzen/verkaeufe/{coinSale}', [CoinController::class, 'destroySale'])->name('coins.sales.destroy');
            Route::put('/einstellungen', SettingController::class)->name('settings.update');
        });

        // Partei
        Route::middleware('role:user')->name('user.')->group(function () {
            Route::get('/saunagaenge', DashboardController::class)->name('dashboard');

            Route::get('/reservieren', [ReservationController::class, 'create'])->name('reservations.create');
            Route::post('/reservieren', [ReservationController::class, 'store'])->name('reservations.store');
            Route::get('/reservierungen/{reservation}/bearbeiten', [ReservationController::class, 'edit'])->name('reservations.edit');
            Route::put('/reservierungen/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
            Route::patch('/reservierungen/{reservation}/absagen', [ReservationController::class, 'cancel'])->name('reservations.cancel');
            Route::patch('/reservierungen/{reservation}/schluessel', [ReservationController::class, 'reportKey'])->name('reservations.report');
            Route::delete('/reservierungen/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

            Route::get('/tagebuch/{reservation?}', [DiaryController::class, 'show'])->name('diary');
            Route::put('/tagebuch/{reservation}', [DiaryController::class, 'update'])->name('diary.update');

            Route::get('/oele/mischungen', [BlendController::class, 'index'])->name('blends.index');
            Route::post('/oele/mischungen', [BlendController::class, 'store'])->name('blends.store');
            Route::put('/oele/mischungen/{blend}', [BlendController::class, 'update'])->name('blends.update');
            Route::delete('/oele/mischungen/{blend}', [BlendController::class, 'destroy'])->name('blends.destroy');

            Route::get('/oele', [OilController::class, 'index'])->name('oils.index');
            Route::post('/oele', [OilController::class, 'store'])->name('oils.store');
            Route::patch('/oele/{oil}', [OilController::class, 'update'])->name('oils.update');
            Route::delete('/oele/{oil}', [OilController::class, 'destroy'])->name('oils.destroy');

            Route::get('/muenzen', [CoinPurchaseController::class, 'index'])->name('coins.index');
            Route::post('/muenzen', [CoinPurchaseController::class, 'store'])->name('coins.store');
            Route::delete('/muenzen/{coinPurchase}', [CoinPurchaseController::class, 'destroy'])->name('coins.destroy');

            Route::get('/jahresbilanz', YearReportController::class)->name('report');
        });
    });
});
