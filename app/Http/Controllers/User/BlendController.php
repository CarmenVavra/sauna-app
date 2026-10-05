<?php

namespace App\Http\Controllers\User;

use App\Actions\SaveBlend;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveBlendRequest;
use App\Models\Blend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BlendController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $blends = $user->blends()->with('ingredients')->get();
        $oilNames = $user->oils()->pluck('name')->all();

        $openBlend = session('blend_open');

        return view('user.blends', compact('blends', 'oilNames', 'openBlend'));
    }

    /** Neue Mischung – aus dem Formular oder aus dem Tagebuch („Als Mischung speichern“, JSON). */
    public function store(SaveBlendRequest $request, SaveBlend $save): RedirectResponse|JsonResponse
    {
        $result = $save->handle($request->user(), $request->validated());
        $message = $this->message('„'.$result['blend']->name.'“ gespeichert.', $result['addedOils']);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'blend' => $result['blend']->toEditorArray(), 'addedOils' => $result['addedOils']]);
        }

        return redirect()->route('user.blends.index')->with('toast', $message)->with('blend_open', $result['blend']->id);
    }

    public function update(SaveBlendRequest $request, Blend $blend, SaveBlend $save): RedirectResponse
    {
        $result = $save->handle($request->user(), $request->validated(), $blend);

        return redirect()->route('user.blends.index')
            ->with('toast', $this->message('„'.$result['blend']->name.'“ geändert.', $result['addedOils']))
            ->with('blend_open', $result['blend']->id);
    }

    public function destroy(Blend $blend): RedirectResponse
    {
        Gate::authorize('delete', $blend);
        $blend->delete();

        return redirect()->route('user.blends.index')->with('toast', '„'.$blend->name.'“ gelöscht.');
    }

    /**
     * @param  list<string>  $addedOils
     */
    private function message(string $message, array $addedOils): string
    {
        return $addedOils === [] ? $message : $message.' Neu in „Meine Öle“: '.implode(', ', $addedOils).'.';
    }
}
