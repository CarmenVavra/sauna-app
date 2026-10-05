<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RegisterParty;
use App\Actions\UpdateParty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartyRequest;
use App\Http\Requests\Admin\UpdatePartyRequest;
use App\Models\Staircase;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Http\RedirectResponse;

class PartyController extends Controller
{
    public function store(StorePartyRequest $request, Staircase $staircase, RegisterParty $register): RedirectResponse
    {
        $data = $request->validated();
        $party = $register->handle($staircase, $data);

        return redirect()->route('admin.staircases.show', $staircase)
            ->with('credentials', $this->credentials('Zugang angelegt', $party, $data['password']));
    }

    public function update(UpdatePartyRequest $request, User $party, UpdateParty $updateParty): RedirectResponse
    {
        $oldUsername = $party->username;
        $party = $updateParty->handle($party, $request->validated());

        $toast = $party->name.' geändert.';
        if ($party->username !== $oldUsername) {
            $toast .= ' Neuer Benutzername: '.$party->username;
        }

        return redirect()->route('admin.staircases.show', $party->staircase_number)->with('toast', $toast);
    }

    public function destroy(User $party): RedirectResponse
    {
        abort_if($party->isAdmin(), 403);
        $staircase = $party->staircase_number;
        $party->delete();

        return redirect()->route('admin.staircases.show', $staircase)->with('toast', $party->name.' entfernt.');
    }

    public function resetPassword(User $party, UsernameGenerator $usernames): RedirectResponse
    {
        abort_if($party->isAdmin(), 403);
        $password = $usernames->startPassword();
        $party->update(['password' => $password, 'must_change_password' => true]);

        return redirect()->route('admin.staircases.show', $party->staircase_number)
            ->with('credentials', $this->credentials('Neues Startpasswort', $party, $password));
    }

    /**
     * Zugangsdaten für den Dialog, der bis zum Schließen sichtbar bleibt (nur für den nächsten Seitenaufruf).
     *
     * @return array{heading: string, name: string, username: string, password: string}
     */
    private function credentials(string $heading, User $party, string $password): array
    {
        return [
            'heading' => $heading,
            'name' => $party->name.' · '.$party->location(),
            'username' => $party->username,
            'password' => $password,
        ];
    }
}
