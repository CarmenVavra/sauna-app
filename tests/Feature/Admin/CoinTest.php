<?php

namespace Tests\Feature\Admin;

use App\Enums\ReservationStatus;
use App\Models\CoinSale;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\View\Components\PartyPicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_coin_page_shows_stock_and_balances(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber']);
        CoinSale::factory()->for($party)->create(['quantity' => 20, 'sold_on' => '2026-03-01']);
        Reservation::factory()->for($party)->on('2026-09-28')->status(ReservationStatus::Returned)
            ->create(['coins_collected' => 3, 'staircase_number' => $this->sauna()->number]);

        $this->actingAs($this->admin())->get('/hauswart/muenzen')
            ->assertStatus(200)
            ->assertSee('383 Münzen im Lager, 17 bei den Parteien.')
            ->assertSee('100,00 €')
            ->assertSee('Anna Gruber')
            ->assertSee('1. Mär 2026')
            ->assertSee('1 Verkäufe in 2026');
    }

    public function test_admin_records_sale(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber']);

        $this->actingAs($this->admin())->post(route('admin.coins.sales.store'), [
            'user_id' => $party->id,
            'quantity' => 10,
            'sold_on' => '2026-10-01',
        ])->assertSessionHas('toast', '10 Münzen an Anna Gruber verkauft · 50,00 €.');

        $sale = CoinSale::query()->sole();
        $this->assertSame(10, $sale->quantity);
        $this->assertSame(5.0, $sale->unit_price);
    }

    public function test_sale_form_offers_searchable_party_field(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber', 'username' => 'anna.gruber', 'apartment' => 7]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/hauswart/muenzen')
            ->assertStatus(200)
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-controls="sale-party-list"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('Name, Stiege oder Top …')
            ->assertSee('anna.gruber')
            ->assertDontSee('Partei wählen …');

        $picker = new PartyPicker(User::query()->parties()->get(), 'sale-party', 'user_id', selected: (string) $party->id);

        $this->assertSame([[
            'id' => $party->id,
            'label' => 'Stiege '.$party->staircase_number.' / Top 7 – Gruber Anna',
            'staircase' => $party->staircase_number,
            'apartment' => 7,
            'words' => 'Anna Gruber anna.gruber',
        ]], $picker->options);
        $this->assertSame('Stiege '.$party->staircase_number.' / Top 7 – Gruber Anna', $picker->initialLabel);

        $unknown = new PartyPicker(User::query()->parties()->get(), 'sale-party', 'user_id', selected: '999');
        $this->assertNull($unknown->selected);
        $this->assertSame('', $unknown->initialLabel);
    }

    public function test_sale_quantity_defaults_to_two(): void
    {
        $this->actingAs($this->admin())->get('/hauswart/muenzen')
            ->assertStatus(200)
            ->assertSee('x-data="liveSum(2, 5)"', false);
    }

    public function test_failed_sale_keeps_chosen_party_in_search_field(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber', 'apartment' => 7]);

        $this->actingAs($this->admin())
            ->from('/hauswart/muenzen')
            ->followingRedirects()
            ->post(route('admin.coins.sales.store'), ['user_id' => $party->id, 'quantity' => 0])
            ->assertSee('Bitte eine Anzahl eingeben.')
            ->assertSee('name="user_id" :value="selected ?? \'\'" value="'.$party->id.'"', false)
            ->assertSee('value="Stiege '.$party->staircase_number.' / Top 7 – Gruber Anna"', false);
    }

    public function test_sales_list_offers_delete_with_confirmation(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber']);
        $sale = CoinSale::factory()->for($party)->create(['quantity' => 3, 'sold_on' => '2026-10-01']);

        $this->actingAs($this->admin())->get('/hauswart/muenzen')
            ->assertSee('Verkauf an Anna Gruber vom 1. Okt 2026 löschen')
            ->assertSee('Verkauf löschen?')
            ->assertSee('3 Münzen an Anna Gruber')
            ->assertSee(route('admin.coins.sales.destroy', $sale));
    }

    public function test_admin_deletes_wrong_sale_and_figures_follow(): void
    {
        $party = $this->party(['first_name' => 'Anna', 'last_name' => 'Gruber']);
        CoinSale::factory()->for($party)->create(['quantity' => 3, 'sold_on' => '2026-10-01']);
        $wrong = CoinSale::factory()->for($party)->create(['quantity' => 3, 'sold_on' => '2026-10-01']);

        $this->actingAs($this->admin())->delete(route('admin.coins.sales.destroy', $wrong))
            ->assertRedirect(route('admin.coins'))
            ->assertSessionHas('toast', 'Verkauf gelöscht: 3 Münzen an Anna Gruber vom 1. Okt 2026.');

        $this->assertModelMissing($wrong);
        $this->assertSame(1, CoinSale::query()->count());
        $this->get('/hauswart/muenzen')->assertSee('397 Münzen im Lager, 3 bei den Parteien.');
    }

    public function test_only_admin_can_delete_sales(): void
    {
        $sale = CoinSale::factory()->for($this->party())->create();

        $this->actingAs($this->party())->delete(route('admin.coins.sales.destroy', $sale))->assertStatus(403);
        $this->assertModelExists($sale);
    }

    public function test_sale_cannot_exceed_stock(): void
    {
        Setting::current()->update(['coins_total' => 5]);

        $this->actingAs($this->admin())->post(route('admin.coins.sales.store'), [
            'user_id' => $this->party()->id,
            'quantity' => 10,
        ])->assertSessionHasErrors(['quantity' => 'Nur noch 5 Münzen im Lager.']);

        $this->assertSame(0, CoinSale::query()->count());
    }

    public function test_sale_requires_party_and_quantity(): void
    {
        $this->actingAs($this->admin())->post(route('admin.coins.sales.store'), ['quantity' => 0])
            ->assertSessionHasErrors(['user_id' => 'Bitte eine Partei wählen.', 'quantity' => 'Bitte eine Anzahl eingeben.']);
    }

    public function test_admin_updates_settings_and_texts_follow(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'coin_price' => 6,
            'minutes_per_coin' => 45,
            'coins_total' => 500,
            'mon_thu_from' => '09:00',
            'mon_thu_until' => '17:00',
            'fri_from' => '08:00',
            'fri_until' => '11:00',
        ])->assertRedirect(route('admin.coins'));

        $settings = Setting::current();
        $this->assertSame(6.0, $settings->coin_price);
        $this->assertSame('11:00', $settings->fri_until);

        $this->actingAs($admin)->get('/hauswart')->assertSee('Schlüsselausgabe heute 09:00–17:00')->assertSee('von 500 Münzen');
    }

    public function test_settings_validation(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'coin_price' => -1,
            'minutes_per_coin' => 0,
            'coins_total' => 400,
            'mon_thu_from' => '18:00',
            'mon_thu_until' => '08:00',
            'fri_from' => 'acht',
            'fri_until' => '12:00',
        ])->assertSessionHasErrors(['coin_price', 'minutes_per_coin', 'mon_thu_until', 'fri_from']);
    }
}
