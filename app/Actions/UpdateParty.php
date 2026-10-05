<?php

namespace App\Actions;

use App\Models\User;
use App\Services\UsernameGenerator;

/**
 * Hauswart ändert Name oder Wohnung einer Partei (z. B. Namenswechsel nach Heirat).
 * Der Benutzername bleibt gleich, außer er soll ausdrücklich angepasst werden.
 */
class UpdateParty
{
    public function __construct(private readonly UsernameGenerator $usernames) {}

    /**
     * @param  array{first_name:string, last_name:string, apartment:int, update_username?:bool}  $data
     */
    public function handle(User $party, array $data): User
    {
        $attributes = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'apartment' => (int) $data['apartment'],
        ];

        if (! empty($data['update_username'])) {
            $attributes['username'] = $this->usernames->generate($attributes['first_name'], $attributes['last_name'], $party);
        }

        $party->update($attributes);

        return $party;
    }
}
