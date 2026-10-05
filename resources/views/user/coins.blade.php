@php($eur = fn ($n) => \App\Support\Fmt::eur($n))
<x-app-layout title="Münzen">
    <x-kicker>Meine Münzen</x-kicker>
    <h1 style="margin:0 0 48px">{{ $coins['left'] }} Münzen übrig.</h1>

    <div class="stats" style="max-width:900px">
        <x-stat :value="$coins['bought']" label="gekauft" />
        <x-stat :value="$coins['used']" label="verbraucht laut Tagebuch" />
        <x-stat :value="$eur($coins['spent'])" label="ausgegeben" />
        <x-stat :value="$eur($coins['avgPrice'])" label="pro Münze im Schnitt" />
    </div>

    <div class="two-col-narrow">
        <section aria-labelledby="buys-title">
            <h2 class="h3" id="buys-title">Käufe</h2>
            @if ($purchases->isEmpty())
                <p class="muted">Noch keine Käufe erfasst.</p>
            @else
                <div class="list-head cols-buys"><span>Datum</span><span class="right">Stk.</span><span class="right">Preis/Stk.</span><span class="right">Summe</span><span></span></div>
                <ul class="list">
                    @foreach ($purchases as $p)
                        <li class="list-row compact cols-buys">
                            <span>{{ \App\Support\Fmt::dayYear($p->purchased_on) }}</span>
                            <span class="right" data-label="Stück">{{ $p->quantity }}</span>
                            <span class="right" data-label="Preis/Stk.">{{ $eur($p->unit_price) }}</span>
                            <span class="right" data-label="Summe">{{ $eur($p->total()) }}</span>
                            <span class="right row-action">
                                <x-confirm id="del-buy-{{ $p->id }}" trigger="Löschen" aria-label="Kauf vom {{ \App\Support\Fmt::dayYear($p->purchased_on) }} löschen"
                                           title="Kauf löschen?"
                                           body="{{ $p->quantity }} Münzen vom {{ \App\Support\Fmt::dayYear($p->purchased_on) }} werden aus Ihrer Aufstellung entfernt."
                                           :action="route('user.coins.destroy', $p)" confirm="Löschen" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="buy-title" x-data="liveSum(@js((int) old('quantity', 2)), @js((float) old('unit_price', $settings->coin_price)))">
            <h2 class="h3" id="buy-title">Kauf erfassen</h2>
            <p class="text-14 muted">Münzen gibt es beim Hauswart, gemeinsam mit dem Schlüssel.</p>
            <form method="POST" action="{{ route('user.coins.store') }}" novalidate>
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(120px,100%),1fr));gap:14px;margin:18px 0">
                    <div class="field">
                        <label for="buy-date">Datum</label>
                        <input id="buy-date" name="purchased_on" class="input" type="date" value="{{ old('purchased_on', $today->toDateString()) }}">
                    </div>
                    <div class="field">
                        <label for="buy-qty">Anzahl</label>
                        <input id="buy-qty" name="quantity" class="input" type="number" min="1" inputmode="numeric" x-model="qty" required>
                    </div>
                    <div class="field">
                        <label for="buy-price">Preis/Stk. (€)</label>
                        <input id="buy-price" name="unit_price" class="input" type="number" step="0.5" min="0" inputmode="decimal" x-model="price" required>
                    </div>
                </div>
                @foreach (['purchased_on', 'quantity', 'unit_price'] as $field)
                    @error($field)<p class="error" role="alert">{{ $message }}</p>@enderror
                @endforeach
                <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                    <button type="submit" class="btn btn-primary">Kauf speichern</button>
                    <span class="text-14" x-text="sum" aria-live="polite"></span>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
