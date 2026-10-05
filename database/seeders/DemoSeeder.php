<?php

namespace Database\Seeders;

use App\Actions\SaveBlend;
use App\Enums\Role;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\UsernameGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo-Daten nach dem Prototyp (UI_MOCKUPS/prototype/Saunabuch.dc.html, Funktion seed()).
 *
 * Der Prototyp spielt in der Woche ab Mo 28.09.2026 („heute“ = Do 01.10.2026).
 * Alle Daten werden um ganze Wochen auf die aktuelle Woche verschoben, damit
 * Wochentage und Wochenend-Regeln stimmen.
 *
 * Zugänge: siehe README.md (Abschnitt „Demo-Zugänge“). Alle Zugänge müssen beim
 * ersten Login ein eigenes Passwort setzen.
 */
class DemoSeeder extends Seeder
{
    public const ADMIN_PASSWORD = 'hauswart-2026';

    public const PARTY_PASSWORD = 'sauna-1234';

    private const VN = ['Johann', 'Elisabeth', 'Franz', 'Anna', 'Josef', 'Theresa', 'Karl', 'Katharina', 'Stefan', 'Sabine', 'Thomas', 'Andrea', 'Michael', 'Claudia', 'Wolfgang', 'Birgit', 'Peter', 'Monika', 'Markus', 'Petra', 'Christian', 'Eva', 'Gerhard', 'Ingrid', 'Lukas', 'Lena', 'David', 'Sophie', 'Florian', 'Helga'];

    private const NN = ['Gruber', 'Bauer', 'Wagner', 'Pichler', 'Steiner', 'Moser', 'Mayer', 'Hofer', 'Leitner', 'Berger', 'Fuchs', 'Eder', 'Fischer', 'Schmid', 'Winkler', 'Weber', 'Schwarz', 'Maier', 'Schneider', 'Reiter', 'Mayr', 'Wimmer', 'Egger', 'Brunner', 'Lang', 'Baumgartner', 'Auer', 'Binder', 'Lechner', 'Haas', 'Kofler'];

    private const DEMO_BLENDS = [
        [
            'name' => 'Freier Atem & Immun-Boost',
            'description' => 'Bei Erkältungszeit. Diese Kombination öffnet die Atemwege maximal und wirkt stark reinigend.',
            'ingredients' => [
                ['oil_name' => 'Eukalyptus', 'drops' => 4, 'note' => null],
                ['oil_name' => 'Pfefferminze', 'drops' => 3, 'note' => null],
                ['oil_name' => 'Teebaum', 'drops' => 2, 'note' => 'oder Zitrone'],
            ],
        ],
        [
            'name' => 'Tiefe Abendentspannung',
            'description' => 'Stressabbau & Schlaf. Für den letzten Saunagang vor dem Schlafengehen.',
            'ingredients' => [
                ['oil_name' => 'Lavendel', 'drops' => 4, 'note' => null],
                ['oil_name' => 'Sibirische Tanne', 'drops' => 3, 'note' => null],
                ['oil_name' => 'Cedarwood', 'drops' => 2, 'note' => 'falls vorhanden – alternativ Weihrauch'],
            ],
        ],
    ];

    private const PROTO_MONDAY = '2026-09-28';

    private const PROTO_TODAY = '2026-10-01';

    private int $shift = 0;

    public function run(): void
    {
        $this->shift = (int) CarbonImmutable::parse(self::PROTO_MONDAY)
            ->diffInDays(CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY), false);
        $now = now();

        Setting::current();

        // Stiegen
        $apartments = [12, 10, 14, 8, 16, 12, 10, 14, 12, 8, 10, 16, 12, 14, 10];
        $saunas = [2, 5, 8, 11, 14];
        DB::table('staircases')->insert(array_map(fn ($w, $i) => [
            'number' => $i + 1, 'apartments' => $w, 'has_sauna' => in_array($i + 1, $saunas, true),
            'created_at' => $now, 'updated_at' => $now,
        ], $apartments, array_keys($apartments)));

        // Parteien
        $parties = [];
        foreach ($apartments as $i => $w) {
            $s = $i + 1;
            for ($t = 1; $t <= $w; $t++) {
                $n = (($s * 7 + $t * 3) % 5 === 0) ? 2 : ((($s + $t) % 11 === 0) ? 0 : 1);
                $nn = self::NN[($s * 11 + $t * 17) % count(self::NN)];
                for ($k = 0; $k < $n; $k++) {
                    $vn = self::VN[($s * 13 + $t * 7 + $k * 5) % count(self::VN)];
                    $last = ($k === 1 && $t % 2) ? self::NN[($s * 5 + $t * 3 + 7) % count(self::NN)] : $nn;
                    $parties[] = ['key' => "p{$s}-{$t}-{$k}", 'first' => $vn, 'last' => $last, 'stiege' => $s, 'top' => $t];
                }
            }
        }
        $me = ['key' => 'me', 'first' => 'Maria', 'last' => 'Huber', 'stiege' => 3, 'top' => 7];
        $mi = array_search(true, array_map(fn ($p) => $p['stiege'] === 3 && $p['top'] === 7, $parties), true);
        if ($mi !== false) {
            $parties[$mi] = $me;
        } else {
            $parties[] = $me;
        }

        $generator = new UsernameGenerator;
        $used = ['hauswart' => true];
        $hash = Hash::make(self::PARTY_PASSWORD);
        $rows = [[
            'first_name' => 'Hauswart', 'last_name' => '', 'username' => 'hauswart',
            'password' => Hash::make(self::ADMIN_PASSWORD), 'role' => Role::Admin->value,
            'staircase_number' => null, 'apartment' => null, 'must_change_password' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]];
        foreach ($parties as &$p) {
            $base = $generator->base($p['first'], $p['last']);
            $u = $base;
            for ($k = 2; isset($used[$u]); $k++) {
                $u = $base.$k;
            }
            $used[$u] = true;
            $p['username'] = $u;
            $rows[] = [
                'first_name' => $p['first'], 'last_name' => $p['last'], 'username' => $u,
                'password' => $hash, 'role' => Role::User->value,
                'staircase_number' => $p['stiege'], 'apartment' => $p['top'], 'must_change_password' => true,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        unset($p);
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        $ids = User::query()->pluck('id', 'username');
        $id = fn (string $key) => $ids[collect($parties)->firstWhere('key', $key)['username']];

        $others = array_values(array_filter($parties, fn ($p) => $p['key'] !== 'me'));
        $regs = [];
        for ($i = 0; count($regs) < 28; $i++) {
            $key = $others[($i * 37 + 11) % count($others)]['key'];
            if (! in_array($key, $regs, true)) {
                $regs[] = $key;
            }
        }

        // Reservierungen
        $res = [];
        $taken = [];
        $busy = [];
        $add = function (string $pid, int $sauna, string $date, bool $weekend, string $status, ?int $coins = null, ?string $note = null) use (&$res, &$taken, &$busy) {
            $res[] = compact('pid', 'sauna', 'date', 'weekend', 'status', 'coins', 'note');
            $taken[$sauna.'|'.$date] = true;
            $busy[$pid.'|'.$date] = true;
        };

        $mine = [['2026-01-10', 5, 1, 4], ['2026-01-21', 2, 0, 2], ['2026-02-07', 5, 1, 3], ['2026-02-18', 8, 0, 2], ['2026-02-26', 2, 0, 2], ['2026-03-07', 5, 1, 4], ['2026-03-19', 11, 0, 2], ['2026-04-11', 5, 1, 3], ['2026-04-22', 2, 0, 2], ['2026-05-13', 14, 0, 1], ['2026-06-10', 2, 0, 1], ['2026-08-20', 8, 0, 2], ['2026-09-09', 2, 0, 2], ['2026-09-19', 5, 1, 3]];
        foreach ($mine as [$d, $s, $w, $c]) {
            $add('me', $s, $d, (bool) $w, 'zurueck', $c);
        }
        $add('me', 11, '2026-09-24', false, 'abgesagt');
        $add('me', 11, '2026-09-30', false, 'ausgegeben');
        $add('me', 5, '2026-10-03', true, 'reserviert', null, 'Wir sind zu zweit – Abholung Freitag um 9.');
        $add('me', 2, '2026-10-15', false, 'reserviert');

        $fixed = [[2, '2026-09-28', 0, 'zurueck', 2, 0], [2, '2026-09-30', 0, 'zurueck', null, 1], [2, '2026-10-01', 0, 'ausgegeben', null, 2], [2, '2026-10-03', 1, 'reserviert', null, 3],
            [5, '2026-09-29', 0, 'zurueck', 3, 4], [5, '2026-10-01', 0, 'reserviert', null, 5],
            [8, '2026-09-28', 0, 'zurueck', 1, 6], [8, '2026-09-30', 0, 'gemeldet', null, 7], [8, '2026-10-02', 0, 'reserviert', null, 8],
            [11, '2026-10-01', 0, 'reserviert', null, 9], [11, '2026-10-03', 1, 'reserviert', null, 10],
            [14, '2026-09-29', 0, 'zurueck', 2, 11], [14, '2026-10-02', 0, 'reserviert', null, 12]];
        foreach ($fixed as [$s, $d, $w, $st, $c, $i]) {
            $add($regs[$i], $s, $d, (bool) $w, $st, $c);
        }

        $base = CarbonImmutable::parse('2026-01-01');
        for ($d = CarbonImmutable::parse('2026-01-05'); $d->year === 2026; $d = $d->addDay()) {
            $iso = $d->toDateString();
            if ($iso >= '2026-09-28' && $iso <= '2026-10-04') {
                continue;
            }
            if ($d->dayOfWeek === CarbonImmutable::SUNDAY) {
                continue;
            }
            $di = (int) $base->diffInDays($d);
            $future = $iso > self::PROTO_TODAY;
            foreach ($saunas as $s) {
                if (isset($taken[$s.'|'.$iso])) {
                    continue;
                }
                if ($this->hsh($di, $s) > ($future ? 0.28 - ($di - 273) * 0.002 : 0.33)) {
                    continue;
                }
                $weekend = $d->dayOfWeek === CarbonImmutable::SATURDAY;
                $pid = $regs[(int) floor($this->hsh($s, $di) * count($regs))];
                if (isset($busy[$pid.'|'.$iso])) {
                    continue; // eine Partei kann nicht zwei Reservierungen am selben Tag haben
                }
                $coins = $future ? null : ($weekend ? 2 + (int) floor($this->hsh($di, $s + 1) * 3) : 1 + (int) floor($this->hsh($di, $s + 2) * 3));
                $add($pid, $s, $iso, $weekend, $future ? 'reserviert' : 'zurueck', $coins);
            }
        }

        foreach (array_chunk($res, 200) as $chunk) {
            DB::table('reservations')->insert(array_map(fn ($r) => [
                'user_id' => $id($r['pid']),
                'staircase_number' => $r['sauna'],
                'date' => $this->shifted($r['date']),
                'is_weekend' => $r['weekend'],
                'status' => $r['status'],
                'coins_collected' => $r['coins'],
                'note' => $r['note'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        // Münzverkäufe (Hauswart)
        $usedBy = [];
        foreach ($res as $r) {
            if ($r['coins']) {
                $usedBy[$r['pid']] = ($usedBy[$r['pid']] ?? 0) + $r['coins'];
            }
        }
        $sales = [];
        $myBuys = [['2026-01-08', 10], ['2026-03-02', 10], ['2026-06-01', 10], ['2026-09-01', 5]];
        foreach ($myBuys as [$d, $q]) {
            $sales[] = ['user_id' => $id('me'), 'sold_on' => $this->shifted($d), 'quantity' => $q, 'unit_price' => 5, 'created_at' => $now, 'updated_at' => $now];
        }
        $i = 0;
        foreach ($usedBy as $pid => $usedCoins) {
            if ($pid === 'me') {
                continue;
            }
            $n = (int) ceil(($usedCoins + floor($this->hsh($i, 3) * 9)) / 10);
            for ($k = 0; $k < $n; $k++) {
                $offset = (int) min(268, floor(($k / $n) * 255 + $this->hsh($i, $k) * 12));
                $sales[] = [
                    'user_id' => $id($pid),
                    'sold_on' => $this->shifted(CarbonImmutable::parse('2026-01-03')->addDays($offset)->toDateString()),
                    'quantity' => 10, 'unit_price' => 5, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            $i++;
        }
        foreach (array_chunk($sales, 200) as $chunk) {
            DB::table('coin_sales')->insert($chunk);
        }

        // Eigene Münzkäufe von Maria Huber
        DB::table('coin_purchases')->insert(array_map(fn ($b) => [
            'user_id' => $id('me'), 'purchased_on' => $this->shifted($b[0]), 'quantity' => $b[1], 'unit_price' => 5,
            'created_at' => $now, 'updated_at' => $now,
        ], $myBuys));

        // Öle: Standardliste für alle, bei Maria mit Marken und Notizen
        $oilNames = config('sauna.default_oils');
        $brands = ['Primavera', 'Taoasis', '', 'Primavera', '', 'Farfalla', '', 'Taoasis', ''];
        $oilNotes = ['Klassiker, frei atmen', 'Sparsam dosieren – brennt schnell', 'Riecht nach Almhütte', '', 'Beruhigend, eher abends', 'Mit Zitrone mischen', '', '', ''];
        $oilRows = [];
        foreach ($ids as $username => $userId) {
            if ($username === 'hauswart') {
                continue;
            }
            $isMe = $userId === $id('me');
            foreach ($oilNames as $k => $name) {
                $oilRows[] = [
                    'user_id' => $userId, 'name' => $name,
                    'brand' => $isMe && $brands[$k] !== '' ? $brands[$k] : null,
                    'note' => $isMe && $oilNotes[$k] !== '' ? $oilNotes[$k] : null,
                    'effect' => config('oil_effects')[$name] ?? null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($oilRows, 300) as $chunk) {
            DB::table('oils')->insert($chunk);
        }

        // Mischungen von Maria Huber (fehlende Öle kommen als doTERRA in „Meine Öle“)
        $maria = User::query()->findOrFail($id('me'));
        foreach (self::DEMO_BLENDS as $blend) {
            app(SaveBlend::class)->handle($maria, $blend, null, 'doTERRA');
        }

        // Saunatagebuch von Maria Huber
        $notes = ['Angenehm trocken, danach Kaltdusche.', 'Ofen braucht gut 40 Minuten zum Aufheizen.', 'Sehr ruhig im Dachgeschoß.', 'Mit Andreas – zweiter Gang am längsten.', 'Bänke frisch geölt, riecht nach Holz.', 'Draußen Schnee, Abkühlen am offenen Fenster.'];
        $combos = [[['Eukalyptus', 3], ['Pfefferminze', 2]], [['Zirbe', 4]], [['Latschenkiefer', 3], ['Zitrone', 2]], [['Lavendel', 3], ['Orange', 3]], [['Zirbe', 3], ['Fichtennadel', 2]]];
        $doses = ['passend', 'zu stark', 'passend', 'zu schwach'];
        $doseNotes = ['passend' => 'Dosierung passt so.', 'zu stark' => 'Pfefferminze brennt in den Augen – nächstes Mal einen Tropfen weniger.', 'zu schwach' => 'Kaum zu riechen, mehr Tropfen nehmen.'];
        $temps = [85, 90, 88, 92, 80, 86, 90, 95, 84, 82, 78, 88, 90, 92];

        $myReturned = Reservation::query()->where('user_id', $id('me'))->where('status', 'zurueck')->orderBy('id')->get();
        foreach ($myReturned as $k => $r) {
            $infusion = $k % 3 !== 2;
            $dose = $doses[$k % 4];
            $entry = $r->diaryEntry()->create([
                'coins' => $r->coins_collected ?? 0,
                'temperature' => $temps[$k % 14],
                'sessions' => 2 + ($k % 2),
                'infusion' => $infusion,
                'dosage' => $infusion ? $dose : null,
                'oil_note' => $infusion ? $doseNotes[$dose] : null,
                'note' => $notes[$k % count($notes)],
            ]);
            if ($infusion) {
                $entry->oils()->createMany(array_map(fn ($o) => ['name' => $o[0], 'drops' => $o[1]], $combos[$k % 5]));
            }
        }
    }

    /** Pseudozufall wie im Prototyp: frac(sin(a·12.9898 + b·78.233) · 43758.5453). */
    private function hsh(float $a, float $b): float
    {
        $x = sin($a * 12.9898 + $b * 78.233) * 43758.5453;

        return $x - floor($x);
    }

    private function shifted(string $date): string
    {
        return CarbonImmutable::parse($date)->addDays($this->shift)->toDateString();
    }
}
