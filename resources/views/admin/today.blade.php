<x-app-layout title="Heute">
    <div class="split">
        <div class="split-main">
            <x-kicker>Heute · {{ $dayLabel }}</x-kicker>
            <h1 class="display" style="max-width:20ch;margin:0 0 64px">{{ $overview['headline'] }}</h1>

            <section class="section" aria-labelledby="mailbox-title">
                <h2 class="h3" id="mailbox-title">Postkasten prüfen</h2>
                <p class="text-14 muted" style="max-width:60ch">Als eingeworfen gemeldete Schlüssel und Schlüssel, deren Reservierung vorbei ist.</p>
                @if ($overview['mailbox']->isEmpty())
                    <p>Nichts zu prüfen.</p>
                @else
                    <ul class="list">
                        @foreach ($overview['mailbox'] as $r)
                            <li class="list-row cols-today">
                                <span>{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span><span class="block">{{ $r->user->name }}</span><span class="list-sub">{{ $r->user->location() }}</span></span>
                                <span><x-status-tag :reservation="$r" :today="$today" /></span>
                                <form method="POST" action="{{ route('admin.reservations.status', $r) }}" class="row-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="zurueck">
                                    <button type="submit" class="btn btn-primary" aria-label="Schlüssel Stiege {{ $r->staircase_number }} von {{ $r->user->name }} ist da">Schlüssel ist da</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="section" aria-labelledby="pickup-title">
                <h2 class="h3" id="pickup-title">Schlüssel abholen lassen</h2>
                <p class="text-14 muted" style="max-width:60ch">Reservierungen der nächsten drei Tage. Für das Wochenende gilt: Abholung bis Freitag {{ $settings->fri_until }}.</p>
                @if ($overview['pickups']->isEmpty())
                    <p>Keine Abholungen offen.</p>
                @else
                    <ul class="list">
                        @foreach ($overview['pickups'] as $r)
                            <li class="list-row cols-today">
                                <span>{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span><span class="block">{{ $r->user->name }}</span><span class="list-sub">{{ $r->user->location() }}</span></span>
                                <span class="text-13">{{ $r->block()->pickup($settings) }}</span>
                                <form method="POST" action="{{ route('admin.reservations.status', $r) }}" class="row-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="ausgegeben">
                                    <button type="submit" class="btn btn-secondary" aria-label="Schlüssel Stiege {{ $r->staircase_number }} an {{ $r->user->name }} ausgegeben">Ausgegeben</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="coins-title">
                <h2 class="h3" id="coins-title">Münzen zählen</h2>
                <p class="text-14 muted" style="max-width:60ch">Beim täglichen Rundgang eingesammelte Münzen der jeweiligen Reservierung zuordnen – so sieht man, wer noch Münzen übrig hat.</p>
                @if ($overview['coins']->isEmpty())
                    <p>Alle Münzen erfasst.</p>
                @else
                    <ul class="list">
                        @foreach ($overview['coins'] as $r)
                            <li class="list-row cols-count">
                                <span>{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span><span class="block">{{ $r->user->name }}</span><span class="list-sub">{{ $r->user->location() }}</span></span>
                                <input form="coins-{{ $r->id }}" class="input" type="number" name="coins" min="0" max="99" inputmode="numeric" placeholder="Anzahl" required
                                       aria-label="Eingesammelte Münzen, {{ $r->saunaLabel() }}, {{ $r->when() }}">
                                <span class="row-action">
                                    <form id="coins-{{ $r->id }}" method="POST" action="{{ route('admin.reservations.coins', $r) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary">Erfassen</button>
                                    </form>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="split-aside" aria-label="Münzen und Schlüsselbrett">
            <x-kicker style="margin-bottom:34px">Münzen im Lager</x-kicker>
            <x-plate-number :value="$coins['stock']" />
            <p class="text-14" style="margin:22px 0 28px">von {{ $coins['total'] }} Münzen · {{ $coins['circulating'] }} bei Parteien</p>
            <div class="kv">
                <div><span>Verkauft {{ $today->year }}</span><span>{{ $coins['soldYear'] }}</span></div>
                <div><span>Eingesammelt {{ $today->year }}</span><span>{{ $coins['usedYear'] }}</span></div>
                <div><span>Einnahmen {{ $today->year }}</span><span>{{ \App\Support\Fmt::eur($coins['revenueYear']) }}</span></div>
            </div>

            <h2 class="h4" style="margin:56px 0 12px">Schlüsselbrett</h2>
            <ul class="list">
                @foreach ($overview['keys'] as $k)
                    <li style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--color-line)">
                        <span><span class="block semibold">{{ $k['sauna'] }}</span><span class="list-sub">{{ $k['where'] }}</span></span>
                        <span class="tag {{ $k['class'] }}">{{ $k['tag'] }}</span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-app-layout>
