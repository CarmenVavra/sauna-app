<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gregor_is_created_with_start_password(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class); // mehrfach ausführbar, ohne Duplikate

        $gregor = User::query()->where('username', 'admin.gregor')->sole();
        $this->assertSame(Role::Admin, $gregor->role);
        $this->assertTrue($gregor->must_change_password);
        $this->assertTrue(Hash::check('password', $gregor->password));
    }

    public function test_admin_gregor_must_change_password_on_first_login(): void
    {
        $this->seed(AdminSeeder::class);

        $this->post('/login', ['username' => 'admin.gregor', 'password' => 'password'])->assertRedirect(route('home'));
        $this->get('/hauswart')->assertRedirect(route('password.edit'));
        $this->get('/passwort')->assertStatus(200)->assertSee('Startpasswort');

        $this->put('/passwort', [
            'current_password' => 'password',
            'password' => 'gregors-neues-passwort',
            'password_confirmation' => 'gregors-neues-passwort',
        ])->assertRedirect(route('home'));

        $this->get('/hauswart')->assertStatus(200);
        $this->assertFalse(User::query()->where('username', 'admin.gregor')->sole()->must_change_password);
    }
}
