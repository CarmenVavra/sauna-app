<?php

namespace Tests\Feature\Admin;

use App\Enums\ReservationStatus;
use App\Models\CoinSale;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_lists_mailbox_pickups_and_coins(): void
    {
        $sauna = $this->sauna(5);
        $anna = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber']);
        $karl = $this->party(['first_name' => 'Karl', 'last_name' => 'Bauer']);
        $eva = $this->party(['first_name' => 'Eva', 'last_name' => 'Moser']);

        Reservation::factory()->for($anna)->on('2026-10-02')->create(['staircase_number' => $sauna->number]);
        Reservation::factory()->for($karl)->on('2026-09-29')->status(ReservationStatus::Issued)->create(['staircase_number' => $sauna->number]);
        Reservation::factory()->for($eva)->on('2026-09-28')->status(ReservationStatus::Returned)->create(['staircase_number' => $sauna->number]);

        $this->actingAs($this->admin())->get('/hauswart')
            ->assertStatus(200)
            ->assertSee('Ein Schlüssel wartet auf Abholung. Im Postkasten ist einer zu prüfen.')
            ->assertSeeInOrder(['Postkasten prüfen', 'Karl Bauer', 'Überfällig', 'Schlüssel abholen lassen', 'Anna Gruber', 'bis Fr 2. Okt, 12:00', 'Münzen zählen', 'Eva Moser'])
            ->assertSee('Schlüsselausgabe heute 08:00–18:00')
            ->assertSee('Bei Karl Bauer seit Di 29. Sep');
    }

    public function test_headline_uses_number_words(): void
    {
        $this->actingAs($this->admin())->get('/hauswart')
            ->assertSee('Kein Schlüssel wartet auf Abholung. Im Postkasten ist nichts zu prüfen.');
    }

    public function test_coin_stock_in_sidebar(): void
    {
        $party = $this->party();
        CoinSale::factory()->for($party)->create(['quantity' => 30]);
        Reservation::factory()->for($party)->on('2026-09-28')->status(ReservationStatus::Returned)
            ->create(['staircase_number' => $this->sauna()->number, 'coins_collected' => 4]);

        $this->actingAs($this->admin())->get('/hauswart')
            ->assertSee('von 400 Münzen · 26 bei Parteien')
            ->assertSee('374');
    }

    public function test_admin_issues_key(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-10-01')->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.reservations.status', $r), ['status' => 'ausgegeben'])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertSame(ReservationStatus::Issued, $r->fresh()->status);
    }

    public function test_admin_confirms_key_in_mailbox(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Reported)->create();

        $this->actingAs($this->admin())->patch(route('admin.reservations.status', $r), ['status' => 'zurueck']);

        $this->assertSame(ReservationStatus::Returned, $r->fresh()->status);
    }

    public function test_admin_rejects_mailbox_report(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Reported)->create();

        $this->actingAs($this->admin())->patch(route('admin.reservations.status', $r), ['status' => 'ausgegeben'])
            ->assertSessionHas('toast', 'Meldung zurückgesetzt – die Partei sieht wieder „Schlüssel offen“.');

        $this->assertSame(ReservationStatus::Issued, $r->fresh()->status);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Returned)->create();

        $this->actingAs($this->admin())->patch(route('admin.reservations.status', $r), ['status' => 'ausgegeben'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ReservationStatus::Returned, $r->fresh()->status);
    }

    public function test_admin_records_collected_coins(): void
    {
        $r = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Returned)
            ->create(['staircase_number' => $this->sauna(5)->number]);

        $this->actingAs($this->admin())->patch(route('admin.reservations.coins', $r), ['coins' => 3])
            ->assertSessionHas('toast', '3 Münzen für Sauna Stiege 5, Mi 30. Sep erfasst.');

        $this->assertSame(3, $r->fresh()->coins_collected);
    }

    public function test_coins_require_returned_key_and_valid_number(): void
    {
        $issued = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Issued)->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.reservations.coins', $issued), ['coins' => 2])->assertSessionHasErrors('coins');
        $this->actingAs($admin)->patch(route('admin.reservations.coins', $issued), ['coins' => -1])->assertSessionHasErrors('coins');
        $this->assertNull($issued->fresh()->coins_collected);
    }
}
