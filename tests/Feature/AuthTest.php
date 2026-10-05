<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Saunabuch')
            ->assertSee('Zugänge vergibt der Hauswart. Eine Selbstregistrierung ist nicht vorgesehen.');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/hauswart')->assertRedirect('/login');
        $this->get('/saunagaenge')->assertRedirect('/login');
    }

    public function test_admin_logs_in_and_lands_on_today(): void
    {
        $this->admin();

        $this->post('/login', ['username' => 'hauswart', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->get('/')->assertRedirect(route('admin.today'));
        $this->assertAuthenticated();
    }

    public function test_party_logs_in_with_case_insensitive_username(): void
    {
        $party = $this->party(['username' => 'maria.huber']);

        $this->post('/login', ['username' => ' Maria.Huber ', 'password' => 'password']);

        $this->assertAuthenticatedAs($party);
        $this->get('/')->assertRedirect(route('user.dashboard'));
    }

    public function test_empty_fields_show_hint(): void
    {
        $this->from('/login')->post('/login', ['username' => '', 'password' => ''])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Bitte Benutzername und Passwort eingeben.']);
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->party(['username' => 'maria.huber']);

        $this->from('/login')->post('/login', ['username' => 'maria.huber', 'password' => 'falsch'])
            ->assertSessionHasErrors(['username' => 'Benutzername oder Passwort stimmt nicht.']);
        $this->assertGuest();
    }

    public function test_removed_party_cannot_log_in(): void
    {
        $party = $this->party(['username' => 'ex.partei']);
        $party->delete();

        $this->post('/login', ['username' => 'ex.partei', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_first_login_forces_password_change(): void
    {
        $party = $this->party(['must_change_password' => true]);

        $this->actingAs($party)->get('/saunagaenge')->assertRedirect(route('password.edit'));
        $this->actingAs($party)->get('/passwort')->assertStatus(200)->assertSee('Neues Passwort setzen');

        $this->actingAs($party)->put('/passwort', [
            'current_password' => 'password',
            'password' => 'neues-passwort',
            'password_confirmation' => 'neues-passwort',
        ])->assertRedirect(route('home'));

        $party->refresh();
        $this->assertFalse($party->must_change_password);
        $this->assertTrue(Hash::check('neues-passwort', $party->password));
        $this->actingAs($party)->get('/saunagaenge')->assertStatus(200);
    }

    public function test_password_change_requires_current_password(): void
    {
        $party = $this->party(['must_change_password' => true]);

        $this->actingAs($party)->put('/passwort', [
            'current_password' => 'falsch',
            'password' => 'neues-passwort',
            'password_confirmation' => 'neues-passwort',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_roles_are_separated(): void
    {
        $this->actingAs($this->party())->get('/hauswart')->assertStatus(403);
        $this->actingAs($this->admin())->get('/saunagaenge')->assertStatus(403);
    }

    public function test_demo_login_is_disabled_by_default(): void
    {
        config(['sauna.demo_logins' => false]);
        $this->admin();

        $this->get('/login')->assertDontSee('Als Hauswart');
        $this->post('/login/demo/admin')->assertStatus(404);
        $this->assertGuest();
    }

    public function test_demo_login_works_when_enabled(): void
    {
        config(['sauna.demo_logins' => true]);
        $admin = $this->admin();

        $this->get('/login')->assertSee('Als Hauswart');
        $this->post('/login/demo/admin')->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->party())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
