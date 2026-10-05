<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Staircase;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Hauswart legt einen Zugang für eine Partei an (keine Selbstregistrierung).
 */
class RegisterParty
{
    public function __construct(private readonly UsernameGenerator $usernames) {}

    /**
     * @param  array{first_name:string, last_name:string, apartment:int, password:string}  $data
     */
    public function handle(Staircase $staircase, array $data): User
    {
        return DB::transaction(function () use ($staircase, $data) {
            $user = User::query()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'username' => $this->usernames->generate($data['first_name'], $data['last_name']),
                'password' => $data['password'],
                'role' => Role::User,
                'staircase_number' => $staircase->number,
                'apartment' => (int) $data['apartment'],
                'must_change_password' => true,
            ]);

            $user->oils()->createMany(
                array_map(fn (string $name) => ['name' => $name, 'effect' => config('oil_effects')[$name] ?? null], config('sauna.default_oils'))
            );

            return $user;
        });
    }
}
