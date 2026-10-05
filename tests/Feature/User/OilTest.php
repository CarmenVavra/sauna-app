<?php

namespace Tests\Feature\User;

use App\Enums\ReservationStatus;
use App\Models\Oil;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OilTest extends TestCase
{
    use RefreshDatabase;

    public function test_oil_list_shows_usage(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Zirbe', 'brand' => 'Primavera']);
        $me->oils()->create(['name' => 'Birke']);
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Returned)->create();
        $entry = $r->diaryEntry()->create(['coins' => 2, 'temperature' => 90, 'sessions' => 2, 'infusion' => true]);
        $entry->oils()->create(['name' => 'Zirbe', 'drops' => 4]);

        $this->actingAs($me)->get('/oele')
            ->assertStatus(200)
            ->assertSee('2 ätherische Öle')
            ->assertSee('1× · 4 Tropfen')
            ->assertSee('noch nicht verwendet')
            ->assertSee('Primavera');
    }

    public function test_oil_page_shows_safety_notice(): void
    {
        $this->actingAs($this->party())->get('/oele')
            ->assertStatus(200)
            ->assertSee('aria-labelledby="oil-safety-title"', false)
            ->assertSeeInOrder([
                'Sicher aufgießen mit ätherischen Ölen',
                'Ätherische Öle von doTERRA sind hochkonzentriert.',
                'Niemals pur auf die Steine:',
                'da sie hochentzündlich sind.',
                'Richtig mischen:',
                'etwa 5 bis 10 Tropfen ätherisches Öl in 1 Liter Aufgusswasser',
                'Rühren Sie das Wasser vor dem Aufgießen gut um.',
            ]);
    }

    public function test_oil_row_shows_effect_summary_and_details(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Eukalyptus', 'effect' => "• Befreiung der Atemwege: Öffnet die Bronchien.\n• Vitalisierend: Vertreibt Müdigkeit."]);
        $me->oils()->create(['name' => 'Rosmarin']);

        $this->actingAs($me)->get('/oele')
            ->assertStatus(200)
            ->assertSee('aria-controls="effect-', false)
            ->assertSee('Befreiung der Atemwege · Vitalisierend')
            ->assertSee('noch nicht erfasst – hinzufügen')
            ->assertSee('Wirkung von Eukalyptus in der Sauna')
            ->assertSee('Öffnet die Bronchien.')
            ->assertSee('Wirkung in der Sauna (optional)');
    }

    public function test_party_saves_effect_and_gets_formatted_points(): void
    {
        $me = $this->party();
        $oil = $me->oils()->create(['name' => 'Zirbe', 'brand' => 'Farfalla']);
        $effect = "• Ruhe: Wirkt ausgleichend.\n• Almwald: Riecht nach Berghütte.";

        $this->actingAs($me)->patchJson(route('user.oils.update', $oil), ['brand' => 'Farfalla', 'note' => '', 'effect' => $effect])
            ->assertOk()
            ->assertJson([
                'message' => 'Zirbe gespeichert.',
                'summary' => 'Ruhe · Almwald',
                'points' => [
                    ['bullet' => true, 'title' => 'Ruhe', 'text' => 'Wirkt ausgleichend.'],
                    ['bullet' => true, 'title' => 'Almwald', 'text' => 'Riecht nach Berghütte.'],
                ],
            ]);

        $this->assertSame($effect, $oil->fresh()->effect);
    }

    public function test_saving_brand_keeps_effect(): void
    {
        $me = $this->party();
        $oil = $me->oils()->create(['name' => 'Zirbe', 'effect' => '• Ruhe: Wirkt ausgleichend.']);

        $this->actingAs($me)->patchJson(route('user.oils.update', $oil), ['brand' => 'Primavera', 'note' => ''])->assertOk();

        $this->assertSame('• Ruhe: Wirkt ausgleichend.', $oil->fresh()->effect);
    }

    public function test_effect_is_limited_and_only_owner_may_edit(): void
    {
        $me = $this->party();
        $oil = $me->oils()->create(['name' => 'Zirbe']);

        $this->actingAs($me)->patchJson(route('user.oils.update', $oil), ['effect' => str_repeat('a', 3001)])
            ->assertStatus(422)->assertJsonValidationErrors('effect');
        $this->actingAs($this->party())->patchJson(route('user.oils.update', $oil), ['effect' => 'fremd'])
            ->assertStatus(403);

        $this->assertNull($oil->fresh()->effect);
    }

    public function test_new_oil_can_be_added_with_effect(): void
    {
        $me = $this->party();

        $this->actingAs($me)->post('/oele', ['name' => 'Rosmarin', 'effect' => '  • Klarer Kopf: Belebt.  '])
            ->assertSessionHas('toast', 'Rosmarin hinzugefügt.');

        $this->assertSame('• Klarer Kopf: Belebt.', $me->oils()->sole()->effect);
    }

    public function test_default_oils_of_existing_parties_get_suggested_effects(): void
    {
        $me = $this->party();
        $eucalyptus = $me->oils()->create(['name' => 'Eukalyptus']);
        $lavender = $me->oils()->create(['name' => 'Lavendel', 'effect' => 'Mein eigener Text']);
        $own = $me->oils()->create(['name' => 'Rosmarin']);

        $migration = require database_path('migrations/2026_10_04_000002_fill_default_oil_effects.php');
        $migration->up();

        $this->assertSame(config('oil_effects.Eukalyptus'), $eucalyptus->fresh()->effect);
        $this->assertStringStartsWith('• Befreiung der Atemwege: Der frische, intensive Duft', $eucalyptus->fresh()->effect);
        $this->assertSame('Mein eigener Text', $lavender->fresh()->effect);
        $this->assertNull($own->fresh()->effect);

        $migration->down();
        $this->assertNull($eucalyptus->fresh()->effect);
        $this->assertSame('Mein eigener Text', $lavender->fresh()->effect);
    }

    public function test_party_adds_oil_and_duplicates_are_rejected(): void
    {
        $me = $this->party();

        $this->actingAs($me)->post('/oele', ['name' => ' Rosmarin ', 'brand' => 'Taoasis'])
            ->assertSessionHas('toast', 'Rosmarin hinzugefügt.');
        $this->actingAs($me)->post('/oele', ['name' => 'rosmarin'])
            ->assertSessionHasErrors(['name' => 'rosmarin ist schon in der Liste.']);

        $this->assertSame(1, $me->oils()->count());
        $this->assertSame('Taoasis', $me->oils()->first()->brand);
    }

    public function test_diary_adds_or_selects_oil_via_json(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Zirbe']);

        $this->actingAs($me)->postJson('/oele', ['name' => 'zirbe', 'select_existing' => 1])
            ->assertOk()->assertJson(['name' => 'Zirbe', 'created' => false]);
        $this->actingAs($me)->postJson('/oele', ['name' => 'Rosmarin', 'select_existing' => 1])
            ->assertOk()->assertJson(['name' => 'Rosmarin', 'created' => true]);

        $this->assertSame(2, $me->oils()->count());
    }

    public function test_party_updates_brand_and_note_inline(): void
    {
        $me = $this->party();
        $oil = $me->oils()->create(['name' => 'Zirbe']);

        $this->actingAs($me)->patchJson(route('user.oils.update', $oil), ['brand' => 'Farfalla', 'note' => 'Almhütte'])
            ->assertOk()->assertJson(['message' => 'Zirbe gespeichert.']);

        $this->assertSame('Farfalla', $oil->fresh()->brand);
    }

    public function test_party_removes_oil(): void
    {
        $me = $this->party();
        $oil = $me->oils()->create(['name' => 'Zirbe']);

        $this->actingAs($me)->delete(route('user.oils.destroy', $oil))->assertSessionHas('toast', 'Zirbe entfernt.');
        $this->assertModelMissing($oil);
    }

    public function test_foreign_oils_are_protected(): void
    {
        $oil = Oil::factory()->for($this->party())->create();
        $me = $this->party();

        $this->actingAs($me)->patch(route('user.oils.update', $oil), ['brand' => 'x'])->assertStatus(403);
        $this->actingAs($me)->delete(route('user.oils.destroy', $oil))->assertStatus(403);
    }
}
