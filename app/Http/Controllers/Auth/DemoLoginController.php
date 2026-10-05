<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Demo-Schnellzugang („Als Hauswart“ / „Als Maria Huber“).
 * Nur aktiv, wenn SAUNA_DEMO_LOGINS=true gesetzt ist.
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('sauna.demo_logins'), 404);

        $user = User::query()->where('username', $role === 'admin' ? 'hauswart' : 'maria.huber')->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
