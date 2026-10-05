<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function __invoke(UpdateSettingsRequest $request): RedirectResponse
    {
        Setting::current()->update($request->validated());

        return redirect()->route('admin.coins')->with('toast', 'Grundwerte gespeichert.');
    }
}
