<?php

namespace Tests\Feature\User;

use App\Enums\ReservationStatus;
use App\Models\CoinPurchase;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_coin_page_shows_summary(): void
    {
        $me = $this->party();
        $me->coinPurchases()->create(['purchased_on' => '2026-01-08', 'quantity' => 10, 'unit_price' => 5]);
        $me->coinPurchases()->create(['purchased_on' => '2026-06-01', 'quantity' => 10, 'unit_price' => 6]);
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Returned)->create();
        $r->diaryEntry()->create(['coins' => 3, 'temperature' => 90, 'sessions' => 2, 'infusion' => false]);

        $this->actingAs($me)->get('/muenzen')
            ->assertStatus(200)
            ->assertSee('17 Münzen übrig.')
            ->assertSee('110,00 €')
            ->assertSee('5,50 €')
            ->assertSeeInOrder(['1. Jun 2026', '8. Jän 2026']);
    }

    public function test_purchase_quantity_defaults_to_two(): void
    {
        $this->actingAs($this->party())->get('/muenzen')
            ->assertStatus(200)
            ->assertSee('x-data="liveSum(2, 5)"', false);
    }

    public function test_low_coins_warning_on_dashboard(): void
    {
        $me = $this->party();
        $me->coinPurchases()->create(['purchased_on' => '2026-01-08', 'quantity' => 2, 'unit_price' => 5]);

        $this->actingAs($me)->get('/saunagaenge')->assertSee('Beim nächsten Schlüssel gleich Münzen mitkaufen.');
    }

    public function test_party_records_and_deletes_purchase(): void
    {
        $me = $this->party();

        $this->actingAs($me)->post('/muenzen', ['purchased_on' => '2026-10-01', 'quantity' => 10, 'unit_price' => 5])
            ->assertSessionHas('toast', '10 Münzen erfasst.');
        $purchase = CoinPurchase::query()->sole();

        $this->actingAs($me)->post('/muenzen', ['quantity' => 0, 'unit_price' => 5])
            ->assertSessionHasErrors(['quantity' => 'Bitte eine Anzahl eingeben.']);

        $this->actingAs($this->party())->delete(route('user.coins.destroy', $purchase))->assertStatus(403);
        $this->actingAs($me)->delete(route('user.coins.destroy', $purchase))->assertSessionHas('toast');
        $this->assertModelMissing($purchase);
    }

    public function test_year_report(): void
    {
        $me = $this->party();
        $me->coinPurchases()->create(['purchased_on' => '2026-01-08', 'quantity' => 10, 'unit_price' => 5]);
        $sauna = $this->sauna(5)->number;

        $a = Reservation::factory()->for($me)->on('2026-01-10')->status(ReservationStatus::Returned)->create(['staircase_number' => $sauna]);
        $entryA = $a->diaryEntry()->create(['coins' => 4, 'temperature' => 85, 'sessions' => 2, 'infusion' => true, 'dosage' => 'passend']);
        $entryA->oils()->create(['name' => 'Zirbe', 'drops' => 4]);
        $b = Reservation::factory()->for($me)->on('2026-03-19')->status(ReservationStatus::Returned)->create(['staircase_number' => $sauna]);
        $b->diaryEntry()->create(['coins' => 2, 'temperature' => 95, 'sessions' => 3, 'infusion' => false]);
        Reservation::factory()->for($me)->on('2026-10-06')->create(['staircase_number' => $sauna]);

        $this->actingAs($me)->get('/jahresbilanz')
            ->assertStatus(200)
            ->assertSee('Jahresbilanz 2026 · bis Do 1. Okt')
            ->assertSee('Saunagänge, 30,00 € für Münzen.')
            ->assertSee('Im Schnitt 15,00 € pro Saunagang. 1 davon an Wochenenden, Zirbe war das meistverwendete Öl.')
            ->assertSee('90 °C')
            ->assertSee('Ø Temperatur · max. 95 °C')
            ->assertSee('Stiege 5')
            ->assertSee('4 Tropfen · 1×')
            ->assertSee('Bei 1 von 1 Aufgüssen passte die Dosierung.');
    }
}
