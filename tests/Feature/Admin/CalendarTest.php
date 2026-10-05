<?php

namespace Tests\Feature\Admin;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_view_shows_reservations_and_free_cells(): void
    {
        $this->sauna(2);
        $this->sauna(5);
        $party = $this->party(['first_name' => 'Maria', 'last_name' => 'Huber']);
        Reservation::factory()->for($party)->on('2026-10-03')->create(['staircase_number' => 5, 'note' => 'Wir sind zu zweit']);

        $this->actingAs($this->admin())->get('/hauswart/kalender')
            ->assertStatus(200)
            ->assertSee('KW 40')
            ->assertSee('Mo 28. Sep – So 4. Okt 2026')
            ->assertSee('Do 1. Okt · heute')
            ->assertSee('Wochenende 3. – So 4. Okt')
            ->assertSee('Maria Huber')
            ->assertSee('Wir sind zu zweit')
            ->assertSee('frei · anlegen');
    }

    public function test_week_navigation(): void
    {
        $this->sauna();

        $this->actingAs($this->admin())->get('/hauswart/kalender?woche=2026-10-07')
            ->assertSee('KW 41')
            ->assertSee('Mo 5. Okt – So 11. Okt 2026');
    }

    public function test_admin_creates_reservation_for_party(): void
    {
        $this->sauna(5);
        $party = $this->party();

        $this->actingAs($this->admin())->post(route('admin.reservations.store'), [
            'user_id' => $party->id,
            'staircase_number' => 5,
            'date' => '2026-10-10',
        ])->assertSessionHas('toast');

        $r = Reservation::query()->sole();
        $this->assertTrue($r->is_weekend);
        $this->assertSame('2026-10-10', $r->date->toDateString());
        $this->assertSame(ReservationStatus::Reserved, $r->status);
    }

    public function test_admin_cannot_double_book(): void
    {
        $this->sauna(5);
        Reservation::factory()->for($this->party())->on('2026-10-06')->create(['staircase_number' => 5]);

        $this->actingAs($this->admin())->post(route('admin.reservations.store'), [
            'user_id' => $this->party()->id,
            'staircase_number' => 5,
            'date' => '2026-10-06',
        ])->assertSessionHasErrors(['date' => 'Die Sauna ist an diesem Tag bereits belegt.']);

        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_admin_reservation_requires_party(): void
    {
        $this->sauna(5);

        $this->actingAs($this->admin())->post(route('admin.reservations.store'), [
            'staircase_number' => 5,
            'date' => '2026-10-06',
        ])->assertSessionHasErrors(['user_id' => 'Bitte eine Partei wählen.']);
    }

    public function test_admin_cancels_reservation(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-10-06')->create();

        $this->actingAs($this->admin())->patch(route('admin.reservations.status', $r), ['status' => 'abgesagt'])
            ->assertSessionHas('toast', 'Reservierung storniert.');

        $this->assertSame(ReservationStatus::Cancelled, $r->fresh()->status);
    }
}
