<?php

namespace Tests\Feature\Admin;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaircaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_staircase_page_lists_apartments_and_parties(): void
    {
        $this->sauna(5);
        $this->party(['first_name' => 'Maria', 'last_name' => 'Huber', 'username' => 'maria.huber', 'apartment' => 7]);

        $this->actingAs($this->admin())->get('/hauswart/stiegen/3')
            ->assertStatus(200)
            ->assertSee('Stiege 3')
            ->assertSee('Top 10')
            ->assertSeeInOrder(['Top 7', 'Maria Huber', 'maria.huber'])
            ->assertSee('Keine Partei registriert')
            ->assertSee('Sauna');
    }

    public function test_default_staircase_is_the_first(): void
    {
        $this->staircase(1);
        $this->staircase(2);

        $this->actingAs($this->admin())->get('/hauswart/stiegen')->assertStatus(200)->assertSee('Stiege 1');
    }

    public function test_admin_updates_staircase(): void
    {
        $staircase = $this->staircase(3, 10);

        $this->actingAs($this->admin())->patch(route('admin.staircases.update', $staircase), [
            'apartments' => 14,
            'has_sauna' => '1',
        ])->assertRedirect(route('admin.staircases.show', $staircase));

        $staircase->refresh();
        $this->assertSame(14, $staircase->apartments);
        $this->assertTrue($staircase->has_sauna);
    }

    public function test_apartments_cannot_drop_below_occupied_top(): void
    {
        $staircase = $this->staircase(3, 10);
        $this->party(['apartment' => 9]);

        $this->actingAs($this->admin())->patch(route('admin.staircases.update', $staircase), [
            'apartments' => 5,
            'has_sauna' => '0',
        ])->assertSessionHasErrors(['apartments' => 'In Top 9 ist noch eine Partei registriert.']);
    }

    public function test_disabling_sauna_with_reservations_needs_confirmation(): void
    {
        $sauna = $this->sauna(5);
        $r = Reservation::factory()->for($this->party())->on('2026-10-06')->create(['staircase_number' => 5]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.staircases.update', $sauna), ['apartments' => 12, 'has_sauna' => '0'])
            ->assertSessionHasErrors('has_sauna');
        $this->assertTrue($sauna->fresh()->has_sauna);

        $this->actingAs($admin)->patch(route('admin.staircases.update', $sauna), ['apartments' => 12, 'has_sauna' => '0', 'confirm_cancel' => '1'])
            ->assertSessionHas('toast', 'Stiege 5 gespeichert. 1 Reservierungen storniert.');
        $this->assertFalse($sauna->fresh()->has_sauna);
        $this->assertSame(ReservationStatus::Cancelled, $r->fresh()->status);
    }

    public function test_admin_registers_party_with_generated_username(): void
    {
        $staircase = $this->staircase(3, 10);
        $this->party(['username' => 'jürgen.mueller']);
        $this->party(['username' => 'juergen.mueller']);

        $this->actingAs($this->admin())->post(route('admin.parties.store', $staircase), [
            'first_name' => 'Jürgen',
            'last_name' => 'Müller',
            'apartment' => 4,
            'password' => 'sauna-4821',
        ])->assertSessionHas('credentials', [
            'heading' => 'Zugang angelegt',
            'name' => 'Jürgen Müller · Stiege 3 · Top 4',
            'username' => 'juergen.mueller2',
            'password' => 'sauna-4821',
        ]);

        $user = User::query()->where('username', 'juergen.mueller2')->sole();
        $this->assertSame(3, $user->staircase_number);
        $this->assertSame(4, $user->apartment);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('sauna-4821', $user->password));
        $this->assertSame(9, $user->oils()->count());
        $this->assertSame(9, $user->oils()->whereNotNull('effect')->count());
        $this->assertSame(config('oil_effects.Eukalyptus'), $user->oils()->where('name', 'Eukalyptus')->value('effect'));
    }

    public function test_party_registration_validates_input(): void
    {
        $staircase = $this->staircase(3, 10);

        $this->actingAs($this->admin())->post(route('admin.parties.store', $staircase), [
            'first_name' => '',
            'last_name' => 'Huber',
            'apartment' => 11,
            'password' => 'kurz',
        ])->assertSessionHasErrors(['first_name', 'apartment', 'password']);
    }

    public function test_admin_removes_party_but_keeps_history(): void
    {
        $party = $this->party();
        $r = Reservation::factory()->for($party)->on('2026-09-20')->status(ReservationStatus::Returned)->create();

        $this->actingAs($this->admin())->delete(route('admin.parties.destroy', $party))
            ->assertRedirect(route('admin.staircases.show', 3));

        $this->assertSoftDeleted($party);
        $this->assertNotNull($r->fresh());
    }

    public function test_admin_resets_party_password(): void
    {
        $party = $this->party();

        $this->actingAs($this->admin())->post(route('admin.parties.password', $party))
            ->assertRedirect(route('admin.staircases.show', 3))
            ->assertSessionHas('credentials');

        $password = session('credentials')['password'];
        $party->refresh();
        $this->assertTrue($party->must_change_password);
        $this->assertTrue(Hash::check($password, $party->password));
        $this->assertFalse(Hash::check('password', $party->password));

        // Der Dialog zeigt die Zugangsdaten auf der Folgeseite – und nur dort.
        $this->get(route('admin.staircases.show', 3))
            ->assertSee('Neues Startpasswort')
            ->assertSee('id="credentials-dialog"', false)
            ->assertSee('<code>'.$party->username.'</code>', false)
            ->assertSee('<code>'.$password.'</code>', false)
            ->assertSee('Kopieren');

        $this->get(route('admin.staircases.show', 3))->assertDontSee('id="credentials-dialog"', false);
    }

    public function test_admin_account_cannot_be_removed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.parties.destroy', $admin))->assertStatus(403);
    }
}
