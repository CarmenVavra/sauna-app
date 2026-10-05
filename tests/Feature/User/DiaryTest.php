<?php

namespace Tests\Feature\User;

use App\Enums\ReservationStatus;
use App\Models\DiaryEntry;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_diary_lists_past_visits(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-09-19')->status(ReservationStatus::Returned)->create(['staircase_number' => $this->sauna(5)->number]);
        Reservation::factory()->for($me)->on('2026-10-06')->create();

        $this->actingAs($me)->get('/tagebuch')
            ->assertStatus(200)
            ->assertSee('0 Einträge')
            ->assertSee('Sa 19. – So 20. Sep · St. 5')
            ->assertSee('Noch kein Eintrag')
            ->assertSee('Samstag, 19. – Sonntag, 20. September')
            ->assertDontSee('Di 6. Okt');

        $this->actingAs($me)->get(route('user.diary', $r))->assertStatus(200)->assertSee('Neuer Eintrag');
    }

    public function test_diary_shows_effect_of_own_oils(): void
    {
        $me = $this->party();
        Reservation::factory()->for($me)->on('2026-09-19')->status(ReservationStatus::Returned)->create();
        $me->oils()->create(['name' => 'Zirbe', 'effect' => "• Ruhe: Wirkt ausgleichend.\n• Almwald: Riecht nach Berghütte."]);
        $me->oils()->create(['name' => 'Rosmarin']);
        $this->party()->oils()->create(['name' => 'Fremdöl', 'effect' => '• Geheim: Nicht meins.']);

        $response = $this->actingAs($me)->get('/tagebuch')
            ->assertStatus(200)
            ->assertSee('class="diary-effect"', false)
            ->assertDontSee('Nicht meins', false);

        $this->assertSame([
            'Zirbe' => ['summary' => 'Ruhe · Almwald', 'points' => [
                ['bullet' => true, 'title' => 'Ruhe', 'text' => 'Wirkt ausgleichend.'],
                ['bullet' => true, 'title' => 'Almwald', 'text' => 'Riecht nach Berghütte.'],
            ]],
            'Rosmarin' => ['summary' => '', 'points' => []],
        ], $response->viewData('oilEffects'));
    }

    public function test_oil_selected_from_diary_returns_its_effect(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Zirbe', 'effect' => '• Ruhe: Wirkt ausgleichend.']);

        $this->actingAs($me)->postJson('/oele', ['name' => 'zirbe', 'select_existing' => 1])
            ->assertOk()
            ->assertJson(['name' => 'Zirbe', 'summary' => 'Ruhe', 'points' => [['title' => 'Ruhe', 'text' => 'Wirkt ausgleichend.']]]);
    }

    public function test_party_saves_entry_with_oils(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Returned)->create();

        $this->actingAs($me)->put(route('user.diary.update', $r), [
            'coins' => 3,
            'temperature' => 92,
            'sessions' => 3,
            'infusion' => 1,
            'dosage' => 'passend',
            'oil_note' => 'Gut so',
            'note' => 'Herrlich',
            'oils' => [['name' => 'Zirbe', 'drops' => 4], ['name' => 'Orange', 'drops' => 2]],
        ])->assertRedirect(route('user.diary', $r))->assertSessionHas('toast', 'Tagebucheintrag für Mi 30. Sep gespeichert.');

        $entry = DiaryEntry::query()->with('oils')->sole();
        $this->assertSame(92, $entry->temperature);
        $this->assertSame(['Zirbe', 'Orange'], $entry->oils->pluck('name')->all());

        $this->actingAs($me)->get('/saunagaenge')->assertSee('Zirbe, Orange')->assertSee('92 °C');
    }

    public function test_entry_without_infusion_drops_oils(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Returned)->create();

        $this->actingAs($me)->put(route('user.diary.update', $r), [
            'coins' => 1, 'temperature' => 80, 'sessions' => 2, 'infusion' => 0,
            'dosage' => 'zu stark', 'oils' => [['name' => 'Zirbe', 'drops' => 4]],
        ]);

        $entry = DiaryEntry::query()->sole();
        $this->assertFalse($entry->infusion);
        $this->assertNull($entry->dosage);
        $this->assertSame(0, $entry->oils()->count());
    }

    public function test_diary_validation(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Returned)->create();

        $this->actingAs($me)->put(route('user.diary.update', $r), [
            'coins' => -1, 'temperature' => 140, 'sessions' => 0, 'infusion' => 1, 'dosage' => 'viel',
        ])->assertSessionHasErrors(['coins', 'temperature', 'sessions', 'dosage']);
    }

    public function test_no_diary_for_future_or_foreign_visits(): void
    {
        $me = $this->party();
        $future = Reservation::factory()->for($me)->on('2026-10-06')->create();
        $foreign = Reservation::factory()->for($this->party())->on('2026-09-30')->status(ReservationStatus::Returned)->create();
        $data = ['coins' => 1, 'temperature' => 80, 'sessions' => 2, 'infusion' => 0];

        $this->actingAs($me)->put(route('user.diary.update', $future), $data)->assertStatus(403);
        $this->actingAs($me)->put(route('user.diary.update', $foreign), $data)->assertStatus(403);
        $this->actingAs($me)->get(route('user.diary', $foreign))->assertStatus(403);
    }
}
