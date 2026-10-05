@php($eur = fn ($n) => \App\Support\Fmt::eur($n))
<x-app-layout title="Münzen">
    <x-kicker>Münzen &amp; Abrechnung</x-kicker>
    <h1 style="margin:0 0 48px">{{ $coins['stock'] }} Münzen im Lager, {{ $coins['circulating'] }} bei den Parteien.</h1>

    <div class="stats">
        <x-stat :value="$coins['total']" label="Münzen gesamt" />
        <x-stat :value="$coins['soldYear']" :label="'verkauft '.$today->year" />
        <x-stat :value="$coins['usedYear']" :label="'eingesammelt '.$today->year" />
        <x-stat :value="$eur($coins['revenueYear'])" :label="'Einnahmen '.$today->year" />
    </div>

    <div class="two-col">
        <div>
            <section aria-labelledby="sale-title" x-data="liveSum(@js((int) old('quantity', 2)), @js($settings->coin_price))">
                <h2 class="h3" id="sale-title">Verkauf erfassen</h2>
                <form method="POST" action="{{ route('admin.coins.sales.store') }}" novalidate>
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(140px,100%),1fr));gap:14px;align-items:end;margin:18px 0 10px">
                        <div style="grid-column:span 2;min-width:0">
                            <x-party-picker id="sale-party" name="user_id" :parties="$parties" :selected="old('user_id')" :invalid="$errors->has('user_id')" />
                        </div>
                        <div class="field">
                            <label for="sale-qty">Anzahl</label>
                            <input id="sale-qty" name="quantity" class="input" type="number" min="1" inputmode="numeric" x-model="qty" required @error('quantity') aria-invalid="true" @enderror>
                        </div>
                        <div class="field">
                            <label for="sale-date">Datum</label>
                            <input id="sale-date" name="sold_on" class="input" type="date" value="{{ old('sold_on', $today->toDateString()) }}">
                        </div>
                    </div>
                    @foreach (['user_id', 'quantity', 'sold_on'] as $field)
                        @error($field)<p class="error" role="alert">{{ $message }}</p>@enderror
                    @endforeach
                    <div style="display:flex;align-items:center;gap:18px;margin:10px 0 56px;flex-wrap:wrap">
                        <button type="submit" class="btn btn-primary">Verkauf erfassen</button>
                        <span class="text-14" x-text="sum" aria-live="polite"></span>
                    </div>
                </form>
            </section>

            <section aria-labelledby="sales-title">
                <h2 class="h4" id="sales-title">Letzte Verkäufe</h2>
                <div class="list-head cols-sales"><span>Datum</span><span>Partei</span><span class="right">Stk.</span><span class="right">Betrag</span><span></span></div>
                <ul class="list">
                    @foreach ($sales as $s)
                        <li class="list-row tight cols-sales">
                            <span>{{ \App\Support\Fmt::dayYear($s->sold_on) }}</span>
                            <span>{{ $s->user->name }} <span class="small muted">· {{ $s->user->shortLocation() }}</span></span>
                            <span class="right" data-label="Stück">{{ $s->quantity }}</span>
                            <span class="right" data-label="Betrag">{{ $eur($s->total()) }}</span>
                            <span class="right row-action">
                                <x-confirm id="del-sale-{{ $s->id }}" trigger="Löschen" aria-label="Verkauf an {{ $s->user->name }} vom {{ \App\Support\Fmt::dayYear($s->sold_on) }} löschen"
                                           title="Verkauf löschen?"
                                           body="{{ $s->quantity }} Münzen an {{ $s->user->name }} ({{ $s->user->shortLocation() }}) vom {{ \App\Support\Fmt::dayYear($s->sold_on) }} über {{ $eur($s->total()) }} werden gelöscht. Lagerbestand, Einnahmen und übrige Münzen der Partei werden entsprechend korrigiert."
                                           :action="route('admin.coins.sales.destroy', $s)" confirm="Löschen" />
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-13 muted" style="margin-top:12px">{{ $salesCount }} Verkäufe in {{ $today->year }}</p>
            </section>
        </div>

        <div>
            <section aria-labelledby="balance-title">
                <h2 class="h3" id="balance-title">Übrige Münzen je Partei</h2>
                <p class="text-14 muted" style="max-width:52ch">Gekauft minus eingesammelt. Da jede Reservierung einen ganzen Tag gilt, lassen sich die Münzen eindeutig zuordnen.</p>
                <div class="list-head cols-balance"><span>Partei</span><span class="right">Gekauft</span><span class="right">Verwendet</span><span class="right">Übrig</span></div>
                <ul class="list">
                    @foreach ($balances as $b)
                        <li class="list-row tight cols-balance">
                            <span>{{ $b['user']->name }} <span class="small muted">· {{ $b['user']->shortLocation() }}</span></span>
                            <span class="right" data-label="Gekauft">{{ $b['bought'] }}</span>
                            <span class="right" data-label="Verwendet">{{ $b['used'] }}</span>
                            <span class="right semibold" data-label="Übrig">{{ $b['left'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="settings-title" style="margin-top:64px">
                <h2 class="h3" id="settings-title">Grundwerte</h2>
                <form method="POST" action="{{ route('admin.settings.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(130px,100%),1fr));gap:16px;margin-top:18px">
                        <div class="field">
                            <label for="coin_price">Preis pro Münze (€)</label>
                            <input id="coin_price" name="coin_price" class="input" type="number" step="0.5" min="0" value="{{ old('coin_price', $settings->coin_price) }}" required>
                        </div>
                        <div class="field">
                            <label for="minutes_per_coin">Minuten pro Münze</label>
                            <input id="minutes_per_coin" name="minutes_per_coin" class="input" type="number" step="5" min="5" value="{{ old('minutes_per_coin', $settings->minutes_per_coin) }}" required>
                        </div>
                        <div class="field">
                            <label for="coins_total">Münzen gesamt</label>
                            <input id="coins_total" name="coins_total" class="input" type="number" min="0" value="{{ old('coins_total', $settings->coins_total) }}" required>
                        </div>
                    </div>
                    <h3 class="h6" style="margin:32px 0 12px">Schlüsselausgabe</h3>
                    <div style="display:grid;grid-template-columns:70px minmax(0,110px) 14px minmax(0,110px);gap:12px;align-items:center;font-size:14px">
                        <span>Mo – Do</span>
                        <input name="mon_thu_from" class="input" type="time" value="{{ old('mon_thu_from', $settings->mon_thu_from) }}" aria-label="Mo – Do von" required>
                        <span aria-hidden="true">–</span>
                        <input name="mon_thu_until" class="input" type="time" value="{{ old('mon_thu_until', $settings->mon_thu_until) }}" aria-label="Mo – Do bis" required>
                        <span>Freitag</span>
                        <input name="fri_from" class="input" type="time" value="{{ old('fri_from', $settings->fri_from) }}" aria-label="Freitag von" required>
                        <span aria-hidden="true">–</span>
                        <input name="fri_until" class="input" type="time" value="{{ old('fri_until', $settings->fri_until) }}" aria-label="Freitag bis" required>
                    </div>
                    @foreach (['coin_price', 'minutes_per_coin', 'coins_total', 'mon_thu_from', 'mon_thu_until', 'fri_from', 'fri_until'] as $field)
                        @error($field)<p class="error" role="alert">{{ $message }}</p>@enderror
                    @endforeach
                    <p class="text-13 muted" style="margin-top:14px;max-width:52ch">Wochenend-Reservierungen sind nur möglich, solange der Schlüssel bis Freitag {{ $settings->fri_until }} abgeholt werden kann.</p>
                    <button type="submit" class="btn btn-secondary">Grundwerte speichern</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
