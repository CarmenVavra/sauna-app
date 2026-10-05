<?php

namespace App\Providers;

use App\Models\User;
use App\View\Composers\LayoutComposer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        Route::model('party', User::class);

        View::composer(['components.app-layout', 'components.guest-layout', 'auth.*', 'admin.*', 'user.*'], LayoutComposer::class);
    }
}
