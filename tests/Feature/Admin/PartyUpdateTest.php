<?php

namespace Tests\Feature\Admin;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartyUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_staircase_page_offers_change_dialog(): void
    {
        $party = $this->party(['first_name' => 'Maria', 'last_name' => 'Huber', 'username' => 'maria.huber']);

        $this->actingAs($this->admin())->get('/hauswart/stiegen/3')
            ->assertStatus(200)
            ->assertSee('Maria Huber ändern')
            ->assertSee('Partei ändern')
            ->assertSee(route('admin.parties.update', $party));
    }

    public function test_name_change_after_marriage_keeps_username_by_default(): void
    {
        $party = $this->party(['first_name' => 'Maria', 'last_name' => 'Huber', 'username' => 'maria.huber', 'apartment' => 7]);
        $visit = Reservation::factory()->for($party)->on('2026-09-20')->status(ReservationStatus::Returned)->create();

        $this->actingAs($this->admin())->patch(route('admin.parties.update', $party), [
            'first_name' => 'Maria',
            'last_name' => 'Huber-Steiner',
            'apartment' => 7,
        ])->assertRedirect(route('admin.staircases.show', 3))
            ->assertSessionHas('toast', 'Maria Huber-Steiner geändert.');

        $party->refresh();
        $this->assertSame('Huber-Steiner', $party->last_name);
        $this->assertSame('maria.huber', $party->username);
        $this->assertTrue(Hash::check('password', $party->password));
        $this->assertSame($party->id, $visit->fresh()->user_id);
    }

    public function test_username_can_follow_the_new_name(): void
    {
        $party = $this->party(['first_name' => 'Maria', 'last_name' => 'Huber', 'username' => 'maria.huber']);
        $this->party(['username' => 'maria.steiner']);

        $this->actingAs($this->admin())->patch(route('admin.parties.update', $party), [
            'first_name' => 'Maria',
            'last_name' => 'Steiner',
            'apartment' => 1,
            'update_username' => '1',
        ])->assertSessionHas('toast', 'Maria Steiner geändert. Neuer Benutzername: maria.steiner2');

        $this->assertSame('maria.steiner2', $party->fresh()->username);

        $this->post('/logout');
        $this->post('/login', ['username' => 'maria.steiner2', 'password' => 'password']);
        $this->assertAuthenticatedAs($party->fresh());
    }

    public function test_unchanged_name_keeps_own_username_when_renaming(): void
    {
        $party = $this->party(['first_name' => 'Maria', 'last_name' => 'Huber', 'username' => 'maria.huber']);

        $this->actingAs($this->admin())->patch(route('admin.parties.update', $party), [
            'first_name' => 'Maria',
            'last_name' => 'Huber',
            'apartment' => 2,
            'update_username' => '1',
        ]);

        $party->refresh();
        $this->assertSame('maria.huber', $party->username);
        $this->assertSame(2, $party->apartment);
    }

    public function test_update_is_validated(): void
    {
        $party = $this->party();

        $this->actingAs($this->admin())->patch(route('admin.parties.update', $party), [
            'first_name' => '',
            'last_name' => '123',
            'apartment' => 11,
        ])->assertSessionHasErrors(['first_name', 'last_name', 'apartment']);
    }

    public function test_only_admin_can_change_parties_and_not_admins(): void
    {
        $party = $this->party();
        $admin = $this->admin();
        $data = ['first_name' => 'X', 'last_name' => 'Y', 'apartment' => 1];

        $this->actingAs($this->party())->patch(route('admin.parties.update', $party), $data)->assertStatus(403);
        $this->actingAs($admin)->patch(route('admin.parties.update', $admin), $data)->assertStatus(403);
    }
}
