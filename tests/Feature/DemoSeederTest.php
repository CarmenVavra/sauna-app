<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke-Test: Mit den Demo-Daten des Prototyps laden alle Seiten fehlerfrei
 * (inkl. Schutz vor N+1 durch preventLazyLoading).
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_matches_prototype(): void
    {
        $this->seed(DemoSeeder::class);

        $maria = User::query()->where('username', 'maria.huber')->sole();
        $this->assertSame(3, $maria->staircase_number);
        $this->assertSame(7, $maria->apartment);
        $this->assertSame(14, $maria->reservations()->whereHas('diaryEntry')->count());
        $this->assertSame(0, Reservation::query()->active()->selectRaw('staircase_number, date, COUNT(*) as n')->groupBy('staircase_number', 'date')->having('n', '>', 1)->get()->count());
    }

    public function test_all_demo_accounts_must_change_password_on_first_login(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(0, User::query()->where('must_change_password', false)->count());

        $maria = User::query()->where('username', 'maria.huber')->sole();
        $this->post('/login', ['username' => 'maria.huber', 'password' => DemoSeeder::PARTY_PASSWORD]);
        $this->assertAuthenticatedAs($maria);
        $this->get('/saunagaenge')->assertRedirect(route('password.edit'));
    }

    public function test_all_pages_render_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::query()->where('username', 'hauswart')->sole();
        $maria = User::query()->where('username', 'maria.huber')->sole();
        $past = $maria->reservations()->whereHas('diaryEntry')->first();
        $upcoming = $maria->reservations()->where('status', 'reserviert')->first();
        User::query()->whereKey([$admin->id, $maria->id])->update(['must_change_password' => false]);
        $admin->refresh();
        $maria->refresh();

        foreach (['/hauswart', '/hauswart/kalender', '/hauswart/kalender?woche=2026-10-05', '/hauswart/stiegen/3', '/hauswart/muenzen'] as $url) {
            $this->actingAs($admin)->get($url)->assertStatus(200);
        }

        foreach (['/saunagaenge', '/reservieren', route('user.reservations.edit', $upcoming), '/tagebuch', route('user.diary', $past), '/oele', '/muenzen', '/jahresbilanz'] as $url) {
            $this->actingAs($maria)->get($url)->assertStatus(200);
        }

        $this->actingAs($admin)->get('/hauswart')->assertSee('Schlüsselbrett')->assertSee('Münzen im Lager');
        $this->actingAs($maria)->get('/saunagaenge')->assertSee('Samstag, 3. – Sonntag, 4. Oktober')->assertSee('Schlüssel offen');
    }
}
