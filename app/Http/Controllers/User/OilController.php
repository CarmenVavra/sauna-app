<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreOilRequest;
use App\Http\Requests\User\UpdateOilRequest;
use App\Models\Oil;
use App\Services\OilUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OilController extends Controller
{
    public function index(Request $request, OilUsage $usage): View
    {
        $oils = $request->user()->oils()->get();
        $usage = $usage->forUser($request->user());

        return view('user.oils', compact('oils', 'usage'));
    }

    /**
     * Legt ein Öl an. Aus dem Tagebuch (JSON) wird ein vorhandenes Öl gleichen Namens
     * stattdessen nur ausgewählt.
     */
    public function store(StoreOilRequest $request): RedirectResponse|JsonResponse
    {
        $existing = $request->existingOil();
        $oil = $existing ?? $request->user()->oils()->create($request->validated());

        if ($request->expectsJson()) {
            $message = $existing ? $oil->name.' ausgewählt.' : $oil->name.' zu „Meine Öle“ hinzugefügt.';

            return response()->json([
                'name' => $oil->name,
                'created' => $existing === null,
                'message' => $message,
                'summary' => $oil->effectSummary(),
                'points' => $oil->effectPoints(),
            ]);
        }

        return redirect()->route('user.oils.index')->with('toast', $oil->name.' hinzugefügt.');
    }

    public function update(UpdateOilRequest $request, Oil $oil): RedirectResponse|JsonResponse
    {
        $oil->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $oil->name.' gespeichert.',
                'summary' => $oil->effectSummary(),
                'points' => $oil->effectPoints(),
            ]);
        }

        return redirect()->route('user.oils.index')->with('toast', $oil->name.' gespeichert.');
    }

    public function destroy(Oil $oil): RedirectResponse
    {
        Gate::authorize('delete', $oil);
        $oil->delete();

        return redirect()->route('user.oils.index')->with('toast', $oil->name.' entfernt.');
    }
}
