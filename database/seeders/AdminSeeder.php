<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Admin-Zugang „admin.gregor“. Das Startpasswort muss beim ersten Login geändert werden.
 *
 * Einzeln ausführen: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public const USERNAME = 'admin.gregor';

    public const START_PASSWORD = 'password';

    public function run(): void
    {
        User::query()->firstOrCreate(
            ['username' => self::USERNAME],
            [
                'first_name' => 'Gregor',
                'last_name' => '',
                'password' => self::START_PASSWORD,
                'role' => Role::Admin,
                'staircase_number' => null,
                'apartment' => null,
                'must_change_password' => true,
            ],
        );
    }
}
