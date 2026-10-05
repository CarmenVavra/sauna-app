<?php

namespace Tests\Feature\User;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sauna(2);
        $this->sauna(5);
    }

    public function test_dashboard_shows_next_visit(): void
    {
        $me = $this->party();
        Reservation::factory()->for($me)->on('2026-10-03')->create(['staircase_number' => 5]);
        Reservation::factory()->for($me)->on('2026-10-15')->create(['staircase_number' => 2]);

        $this->actingAs($me)->get('/saunagaenge')
            ->assertStatus(200)
            ->assertSee('Samstag, 3. – Sonntag, 4. Oktober')
            ->assertSee('bis Fr 2. Okt, 12:00 beim Hauswart')
            ->assertSee('So 4. Okt abends, Postkasten')
            ->assertSeeInOrder(['Weitere Termine', 'Do 15. Okt', 'Abholung bis Do 15. Okt, 18:00']);
    }

    public function test_dashboard_without_reservations(): void
    {
        $this->actingAs($this->party())->get('/saunagaenge')->assertSee('Kein Saunagang geplant.');
    }

    public function test_reservation_form_shows_availability(): void
    {
        $me = $this->party();
        Reservation::factory()->for($this->party())->on('2026-10-01')->create(['staircase_number' => 2]);

        $this->actingAs($me)->get('/reservieren')
            ->assertStatus(200)
            ->assertSee('Sauna reservieren')
            ->assertSee('frei ab Fr 2. Okt')
            ->assertSee('frei ab Do 1. Okt')
            ->assertSee('Oktober 2026');
    }

    public function test_party_books_a_weekday(): void
    {
        $me = $this->party();

        $this->actingAs($me)->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-06', 'note' => 'Abholung um 17 Uhr'])
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('toast', 'Reserviert: Di 6. Okt, Sauna Stiege 5. Schlüssel bis Di 6. Okt, 18:00 abholen.');

        $r = Reservation::query()->sole();
        $this->assertSame($me->id, $r->user_id);
        $this->assertFalse($r->is_weekend);
        $this->assertSame('Abholung um 17 Uhr', $r->note);
        $this->assertSame(ReservationStatus::Reserved, $r->status);
    }

    public function test_sunday_books_the_whole_weekend(): void
    {
        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-11']);

        $r = Reservation::query()->sole();
        $this->assertSame('2026-10-10', $r->date->toDateString());
        $this->assertTrue($r->is_weekend);
    }

    public function test_past_days_are_rejected(): void
    {
        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-09-30'])
            ->assertSessionHasErrors(['date' => 'Dieser Tag liegt in der Vergangenheit.']);
    }

    public function test_weekend_is_closed_once_friday_has_passed(): void
    {
        $this->travelTo('2026-10-03 09:00');

        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-03'])
            ->assertSessionHasErrors(['date' => 'Für dieses Wochenende kann der Schlüssel nicht mehr bis Freitag abgeholt werden.']);
    }

    public function test_taken_day_is_rejected(): void
    {
        Reservation::factory()->for($this->party())->on('2026-10-06')->create(['staircase_number' => 5]);

        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-06'])
            ->assertSessionHasErrors(['date' => 'Die Sauna ist an diesem Tag bereits belegt.']);
    }

    public function test_cancelled_day_can_be_booked_again(): void
    {
        Reservation::factory()->for($this->party())->on('2026-10-06')->status(ReservationStatus::Cancelled)->create(['staircase_number' => 5]);

        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-06'])
            ->assertSessionHasNoErrors();
    }

    public function test_party_cannot_book_two_saunas_on_the_same_day(): void
    {
        $me = $this->party();
        Reservation::factory()->for($me)->on('2026-10-06')->create(['staircase_number' => 2]);

        $this->actingAs($me)->post('/reservieren', ['staircase_number' => 5, 'date' => '2026-10-06'])
            ->assertSessionHasErrors(['date' => 'Sie haben an diesem Tag bereits eine Reservierung.']);
    }

    public function test_staircase_without_sauna_is_rejected(): void
    {
        $this->actingAs($this->party())->post('/reservieren', ['staircase_number' => 3, 'date' => '2026-10-06'])
            ->assertSessionHasErrors('staircase_number');
    }

    public function test_party_edits_own_reservation(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-10-06')->create(['staircase_number' => 5]);

        $this->actingAs($me)->get(route('user.reservations.edit', $r))->assertStatus(200)->assertSee('Termin ändern');
        $this->actingAs($me)->put(route('user.reservations.update', $r), ['staircase_number' => 2, 'date' => '2026-10-10'])
            ->assertSessionHas('toast');

        $r->refresh();
        $this->assertSame(2, $r->staircase_number);
        $this->assertTrue($r->is_weekend);
    }

    public function test_editing_keeps_own_day_free(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-10-06')->create(['staircase_number' => 5]);

        $this->actingAs($me)->put(route('user.reservations.update', $r), ['staircase_number' => 5, 'date' => '2026-10-06', 'note' => 'neu'])
            ->assertSessionHasNoErrors();
        $this->assertSame('neu', $r->fresh()->note);
    }

    public function test_party_cannot_edit_foreign_or_issued_reservation(): void
    {
        $me = $this->party();
        $foreign = Reservation::factory()->for($this->party())->on('2026-10-06')->create();
        $issued = Reservation::factory()->for($me)->on('2026-10-01')->status(ReservationStatus::Issued)->create();

        $this->actingAs($me)->get(route('user.reservations.edit', $foreign))->assertStatus(403);
        $this->actingAs($me)->put(route('user.reservations.update', $foreign), ['staircase_number' => 5, 'date' => '2026-10-07'])->assertStatus(403);
        $this->actingAs($me)->patch(route('user.reservations.cancel', $issued))->assertStatus(403);
    }

    public function test_party_cancels_and_deletes(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-10-06')->create();

        $this->actingAs($me)->delete(route('user.reservations.destroy', $r))->assertStatus(403);

        $this->actingAs($me)->patch(route('user.reservations.cancel', $r))->assertSessionHas('toast', 'Abgesagt: Di 6. Okt.');
        $this->assertSame(ReservationStatus::Cancelled, $r->fresh()->status);
        $this->actingAs($me)->get('/saunagaenge')->assertSeeInOrder(['Abgesagt', 'Di 6. Okt']);

        $this->actingAs($me)->delete(route('user.reservations.destroy', $r))->assertRedirect(route('user.dashboard'));
        $this->assertSoftDeleted($r);
    }

    public function test_party_reports_key_in_mailbox(): void
    {
        $me = $this->party();
        $r = Reservation::factory()->for($me)->on('2026-09-30')->status(ReservationStatus::Issued)->create(['staircase_number' => 5]);

        $this->actingAs($me)->get('/saunagaenge')
            ->assertSee('Schlüssel offen')
            ->assertSee('Der Schlüssel für die Sauna Stiege 5 vom Mi 30. Sep ist noch nicht zurückgemeldet.');

        $this->actingAs($me)->patch(route('user.reservations.report', $r))
            ->assertSessionHas('toast', 'Danke! Der Hauswart prüft den Postkasten.');
        $this->assertSame(ReservationStatus::Reported, $r->fresh()->status);
    }
}
