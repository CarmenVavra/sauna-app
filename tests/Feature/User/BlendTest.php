<?php

namespace Tests\Feature\User;

use App\Enums\ReservationStatus;
use App\Models\Blend;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlendTest extends TestCase
{
    use RefreshDatabase;

    public function test_oil_pages_have_tabs_and_blend_page_lists_blends_without_templates(): void
    {
        $me = $this->party();
        Blend::factory()->for($me)->withIngredients([['Eukalyptus', 4], ['Pfefferminze', 3], ['Teebaum', 2, 'oder Zitrone']])
            ->create(['name' => 'Freier Atem & Immun-Boost', 'description' => 'Bei Erkältungszeit.']);

        $this->actingAs($me)->get('/oele')->assertSee('aria-label="Öle"', false)->assertSee(route('user.blends.index'));

        $this->actingAs($me)->get('/oele/mischungen')
            ->assertStatus(200)
            ->assertSee('1 Mischung')
            ->assertSeeInOrder(['Freier Atem &amp; Immun-Boost', 'Bei Erkältungszeit.', '4 Tropfen', 'Eukalyptus', '2 Tropfen', 'Teebaum', 'oder Zitrone'], false)
            ->assertSee('Insgesamt 9 Tropfen auf 1 Liter Aufgusswasser')
            ->assertSee('Neue Mischung')
            ->assertDontSee('Vorlagen')
            ->assertDontSee('Übernehmen')
            ->assertDontSee('Tiroler Waldspaziergang');

        $this->actingAs($me)->post('/oele/mischungen/vorlagen/freier-atem')->assertNotFound();
    }

    public function test_blend_list_is_collapsible_with_open_and_close_all(): void
    {
        $me = $this->party();
        $blend = Blend::factory()->for($me)->withIngredients([['Wildorange', 4], ['Zitrone', 3], ['Pfefferminze', 2]])
            ->create(['name' => 'Gute-Laune-Aufguss', 'description' => 'Energie & Vitalität.']);
        Blend::factory()->for($me)->withIngredients([['Lavendel', 8], ['Zirbe', 4]])->create(['name' => 'Kräftig']);

        $this->actingAs($me)->get('/oele/mischungen')
            ->assertSee('blendAccordion(', false)
            ->assertSee('Alle öffnen')
            ->assertSee('Alle schließen')
            ->assertSee('aria-controls="blend-'.$blend->id.'-body"', false)
            ->assertSee('id="blend-'.$blend->id.'-body" x-show="isOpen('.$blend->id.')" x-cloak', false)
            ->assertSee('9 Tropfen</span> · Wildorange, Zitrone, Pfefferminze', false)
            ->assertSee('<span class="alert-text">12 Tropfen</span>', false)
            ->assertSeeInOrder(['Gute-Laune-Aufguss', 'Energie &amp; Vitalität.', '4 Tropfen'], false);
    }

    public function test_saved_blend_is_opened_after_redirect(): void
    {
        $me = $this->party();

        $this->actingAs($me)->post(route('user.blends.store'), ['name' => 'Morgen', 'ingredients' => [['oil_name' => 'Zitrone', 'drops' => 3]]])
            ->assertSessionHas('blend_open', $me->blends()->sole()->id);

        $blend = $me->blends()->sole();
        $this->actingAs($me)->put(route('user.blends.update', $blend), ['name' => 'Morgen', 'ingredients' => [['oil_name' => 'Zitrone', 'drops' => 4]]])
            ->assertSessionHas('blend_open', $blend->id);
    }

    public function test_empty_list_has_no_accordion_tools(): void
    {
        $this->actingAs($this->party())->get('/oele/mischungen')
            ->assertSee('Noch keine Mischungen.')
            ->assertDontSee('Alle öffnen');
    }

    public function test_party_creates_blend_and_missing_oils_are_added(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Eukalyptus']);

        $this->actingAs($me)->post(route('user.blends.store'), [
            'name' => ' Mein Atem ',
            'description' => 'Für Schnupfentage',
            'ingredients' => [
                ['oil_name' => 'eukalyptus', 'drops' => 4, 'note' => ''],
                ['oil_name' => '', 'drops' => 3],
                ['oil_name' => 'Rosmarin', 'drops' => 2, 'note' => 'sparsam'],
            ],
        ])->assertRedirect(route('user.blends.index'))
            ->assertSessionHas('toast', '„Mein Atem“ gespeichert. Neu in „Meine Öle“: Rosmarin.');

        $blend = $me->blends()->with('ingredients')->sole();
        $this->assertSame('Mein Atem', $blend->name);
        $this->assertSame([
            ['name' => 'Eukalyptus', 'drops' => 4, 'note' => null],
            ['name' => 'Rosmarin', 'drops' => 2, 'note' => 'sparsam'],
        ], $blend->toEditorArray()['ingredients']);
        $this->assertSame(['Eukalyptus', 'Rosmarin'], $me->oils()->pluck('name')->all());
    }

    public function test_blend_is_validated(): void
    {
        $me = $this->party();
        Blend::factory()->for($me)->create(['name' => 'Abend']);

        $this->actingAs($me)->post(route('user.blends.store'), ['name' => 'Abend', 'ingredients' => [['oil_name' => '', 'drops' => 2]]])
            ->assertSessionHasErrors(['name' => 'Eine Mischung mit diesem Namen gibt es schon.', 'ingredients' => 'Bitte mindestens ein Öl angeben.']);

        $this->actingAs($me)->post(route('user.blends.store'), [
            'name' => 'Neu',
            'ingredients' => [['oil_name' => 'Zirbe', 'drops' => 25], ['oil_name' => 'zirbe', 'drops' => 2]],
        ])->assertSessionHasErrors(['ingredients.0.drops', 'ingredients']);

        $this->assertSame(1, Blend::query()->count());
    }

    public function test_failed_edit_reopens_dialog_with_input(): void
    {
        $me = $this->party();
        Blend::factory()->for($me)->create(['name' => 'Morgen']);
        $blend = Blend::factory()->for($me)->withIngredients([['Lavendel', 4]])->create(['name' => 'Abend']);

        $this->actingAs($me)->from('/oele/mischungen')->followingRedirects()
            ->put(route('user.blends.update', $blend), ['blend_form' => (string) $blend->id, 'name' => 'Morgen', 'ingredients' => [['oil_name' => 'Zirbe', 'drops' => 2]]])
            ->assertSeeInOrder(['id="edit-blend-'.$blend->id.'"', 'x-init="$el.showModal()"', 'Eine Mischung mit diesem Namen gibt es schon.'], false)
            ->assertSee('value="Morgen"', false);

        $this->assertSame('Abend', $blend->fresh()->name);
    }

    public function test_party_updates_blend_and_ingredients_are_replaced(): void
    {
        $me = $this->party();
        $blend = Blend::factory()->for($me)->withIngredients([['Lavendel', 4], ['Zirbe', 3]])->create(['name' => 'Abend']);

        $this->actingAs($me)->put(route('user.blends.update', $blend), [
            'name' => 'Abendruhe',
            'ingredients' => [['oil_name' => 'Lavendel', 'drops' => 5], ['oil_name' => 'Orange', 'drops' => 2]],
        ])->assertSessionHas('toast', '„Abendruhe“ geändert. Neu in „Meine Öle“: Lavendel, Orange.');

        $blend->refresh()->load('ingredients');
        $this->assertSame('Abendruhe', $blend->name);
        $this->assertSame(['Lavendel', 'Orange'], $blend->ingredients->pluck('oil_name')->all());
        $this->assertSame(7, $blend->totalDrops());
    }

    public function test_too_many_drops_are_flagged(): void
    {
        $me = $this->party();
        Blend::factory()->for($me)->withIngredients([['Lavendel', 8], ['Zirbe', 4]])->create(['name' => 'Kräftig']);

        $this->actingAs($me)->get('/oele/mischungen')
            ->assertSee('Insgesamt 12 Tropfen auf 1 Liter Aufgusswasser – mehr als die empfohlenen 5 bis 10 Tropfen');
    }

    public function test_party_deletes_blend_but_oils_stay(): void
    {
        $me = $this->party();
        $me->oils()->create(['name' => 'Lavendel']);
        $blend = Blend::factory()->for($me)->withIngredients([['Lavendel', 4]])->create(['name' => 'Abend']);

        $this->actingAs($me)->delete(route('user.blends.destroy', $blend))->assertSessionHas('toast', '„Abend“ gelöscht.');

        $this->assertModelMissing($blend);
        $this->assertDatabaseCount('blend_ingredients', 0);
        $this->assertSame(1, $me->oils()->count());
    }

    public function test_foreign_blends_are_protected_and_admin_has_no_blends(): void
    {
        $blend = Blend::factory()->for($this->party())->withIngredients([['Lavendel', 4]])->create();
        $data = ['name' => 'Fremd', 'ingredients' => [['oil_name' => 'Zirbe', 'drops' => 2]]];

        $this->actingAs($this->party())->put(route('user.blends.update', $blend), $data)->assertStatus(403);
        $this->actingAs($this->party())->delete(route('user.blends.destroy', $blend))->assertStatus(403);
        $this->actingAs($this->admin())->get('/oele/mischungen')->assertStatus(403);
        $this->assertModelExists($blend);
    }

    public function test_diary_offers_blends_and_saves_selection_as_blend(): void
    {
        $me = $this->party();
        Reservation::factory()->for($me)->on('2026-09-19')->status(ReservationStatus::Returned)->create();
        Blend::factory()->for($me)->withIngredients([['Lavendel', 4], ['Zirbe', 3]])->create(['name' => 'Abend']);

        $response = $this->actingAs($me)->get('/tagebuch')
            ->assertStatus(200)
            ->assertSee('Mischung verwenden')
            ->assertSee('Als Mischung speichern');
        $this->assertSame('Abend', $response->viewData('blends')[0]['name']);
        $this->assertSame([['name' => 'Lavendel', 'drops' => 4, 'note' => null], ['name' => 'Zirbe', 'drops' => 3, 'note' => null]], $response->viewData('blends')[0]['ingredients']);

        $this->actingAs($me)->postJson(route('user.blends.store'), [
            'name' => 'Morgenfrische',
            'ingredients' => [['oil_name' => 'Zitrone', 'drops' => 3], ['oil_name' => 'Pfefferminze', 'drops' => 1]],
        ])->assertOk()->assertJson([
            'message' => '„Morgenfrische“ gespeichert. Neu in „Meine Öle“: Zitrone, Pfefferminze.',
            'blend' => ['name' => 'Morgenfrische', 'ingredients' => [['name' => 'Zitrone', 'drops' => 3], ['name' => 'Pfefferminze', 'drops' => 1]]],
            'addedOils' => ['Zitrone', 'Pfefferminze'],
        ]);

        $this->actingAs($me)->postJson(route('user.blends.store'), ['name' => 'Morgenfrische', 'ingredients' => [['oil_name' => 'Zitrone', 'drops' => 3]]])
            ->assertStatus(422)->assertJsonValidationErrors(['name' => 'Eine Mischung mit diesem Namen gibt es schon.']);
    }
}
