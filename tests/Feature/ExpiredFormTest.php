<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 419 „Page Expired“: Ein abgelaufenes oder doppelt abgeschicktes Formular führt
 * zurück zum Formular bzw. zur Anmeldung, statt eine Fehlerseite zu zeigen.
 */
class ExpiredFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // In Tests ist die CSRF-Prüfung sonst abgeschaltet.
        $this->app['env'] = 'local';
    }

    public function test_login_form_with_stale_token_returns_to_login_with_hint(): void
    {
        $this->admin();

        $this->withSession(['_token' => 'current-token'])
            ->from('/login')
            ->post('/login', ['_token' => 'stale-token', 'username' => 'hauswart', 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHas('toast', 'Die Seite war abgelaufen. Bitte noch einmal versuchen.')
            ->assertSessionHasInput('username', 'hauswart')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $this->get('/login')->assertStatus(200)->assertSee('Die Seite war abgelaufen. Bitte noch einmal versuchen.');
    }

    public function test_second_login_submit_does_not_end_on_419(): void
    {
        $this->admin();

        $token = 'login-page-token';
        $this->withSession(['_token' => $token])
            ->post('/login', ['_token' => $token, 'username' => 'hauswart', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();

        // Zweites Absenden desselben Formulars: Die Sitzung wurde beim Login neu angelegt.
        $this->post('/login', ['_token' => $token, 'username' => 'hauswart', 'password' => 'password'])
            ->assertStatus(302);
    }

    public function test_logged_in_user_with_stale_form_is_sent_back(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['_token' => 'current-token'])
            ->from('/hauswart/muenzen')
            ->post(route('admin.coins.sales.store'), ['_token' => 'stale-token', 'user_id' => $this->party()->id, 'quantity' => 2])
            ->assertRedirect('/hauswart/muenzen')
            ->assertSessionHas('toast', 'Die Seite war abgelaufen. Bitte noch einmal versuchen.')
            ->assertSessionHasInput('quantity', 2);
    }

    public function test_json_requests_still_receive_419(): void
    {
        $this->actingAs($this->party())
            ->withSession(['_token' => 'current-token'])
            ->postJson(route('user.oils.store'), ['_token' => 'stale-token', 'name' => 'Zirbe'])
            ->assertStatus(419);
    }
}
