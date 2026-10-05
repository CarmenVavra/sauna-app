<x-app-layout title="Kalender">
    <div x-data="{ sel: { sauna: '', date: '', when: '', pick: '' } }">
        <div class="page-head">
            <div>
                <x-kicker>Reservierungskalender · {{ $calendar['kw'] }}</x-kicker>
                <h1 class="mb-0">{{ $calendar['title'] }}</h1>
            </div>
            <nav class="btn-row" aria-label="Woche wechseln">
                <a class="btn btn-secondary" href="{{ route('admin.calendar', ['woche' => $calendar['prev']]) }}">← Vorwoche</a>
                <a class="btn btn-secondary" href="{{ route('admin.calendar', ['woche' => $calendar['current']]) }}">Diese Woche</a>
                <a class="btn btn-secondary" href="{{ route('admin.calendar', ['woche' => $calendar['next']]) }}">Nächste Woche →</a>
            </nav>
        </div>

        <div class="week-scroll" tabindex="0" role="region" aria-label="Wochenplan, horizontal scrollbar">
            <div class="week-grid">
                <div></div>
                @foreach ($calendar['head'] as $h)
                    <div @class(['week-head', 'is-today' => $h['today']])>{{ $h['label'] }}</div>
                @endforeach

                @foreach ($calendar['rows'] as $row)
                    <div class="week-sauna">{{ $row['sauna'] }}</div>
                    @foreach ($row['cells'] as $c)
                        @if ($c['type'] === 'reservation')
                            @php($r = $c['reservation'])
                            <button type="button" class="card week-card" data-dialog-open="res-{{ $r->id }}" aria-haspopup="dialog"
                                    aria-label="{{ $row['sauna'] }}, {{ $r->when() }}: {{ $r->user->name }}, {{ $r->statusLabel($today) }}">
                                <span><span class="name">{{ $r->user->name }}</span><span class="list-sub">{{ $r->user->location() }}</span></span>
                                <span><x-status-tag :reservation="$r" :today="$today" /></span>
                            </button>
                        @elseif ($c['type'] === 'free')
                            <button type="button" class="btn btn-secondary btn-dashed week-free" aria-haspopup="dialog"
                                    aria-label="{{ $row['sauna'] }}, {{ $c['when'] }}: frei – Reservierung anlegen"
                                    @click="sel = @js(['sauna' => $c['sauna'], 'date' => $c['date'], 'when' => $c['when'], 'pick' => $c['pick']]); $refs.newRes.showModal()">frei · anlegen</button>
                        @else
                            <div class="week-past"><span aria-hidden="true">—</span><span class="sr-only">vergangen, frei</span></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="legend">
            <span class="muted">Ablauf</span>
            <span class="tag tag-outline">Schlüssel abzuholen</span><span aria-hidden="true">→</span>
            <span class="tag tag-accent">Schlüssel ausgegeben</span><span aria-hidden="true">→</span>
            <span class="tag tag-accent-2">Eingeworfen gemeldet</span><span aria-hidden="true">→</span>
            <span class="tag tag-neutral">Schlüssel zurück</span>
            <span class="muted" style="margin-left:12px">Samstag und Sonntag werden als ein Wochenende reserviert.</span>
        </div>

        {{-- Dialog: neue Reservierung für eine Partei --}}
        <dialog x-ref="newRes" id="new-reservation" class="dialog" aria-labelledby="new-res-title">
            <form method="POST" action="{{ route('admin.reservations.store') }}" style="display:flex;flex-direction:column;gap:15px">
                @csrf
                <input type="hidden" name="staircase_number" :value="sel.sauna">
                <input type="hidden" name="date" :value="sel.date">
                <p class="kicker mb-0">Neue Reservierung</p>
                <h2 class="dialog-title" id="new-res-title" x-text="sel.when + ' · Sauna Stiege ' + sel.sauna"></h2>
                <div class="field">
                    <label for="new-res-party">Für Partei</label>
                    <select id="new-res-party" name="user_id" class="input" required x-ref="party" @change="$refs.save.disabled = !$event.target.value">
                        <option value="">Partei wählen …</option>
                        @foreach ($parties as $p)
                            <option value="{{ $p->id }}">Stiege {{ $p->staircase_number }} / Top {{ $p->apartment }} – {{ $p->last_name }} {{ $p->first_name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-14 mb-0">Schlüssel abholen: <span x-text="sel.pick"></span></p>
                <div class="dialog-actions">
                    <button type="button" class="btn btn-secondary" data-dialog-close>Abbrechen</button>
                    <button type="submit" class="btn btn-primary" x-ref="save" disabled>Reservieren</button>
                </div>
            </form>
        </dialog>

        {{-- Dialoge: Details je Reservierung --}}
        @foreach ($calendar['reservations'] as $r)
            <dialog id="res-{{ $r->id }}" class="dialog" aria-labelledby="res-{{ $r->id }}-title">
                <p class="kicker mb-0">{{ $r->when() }} · Sauna {{ $r->saunaLabel() }}</p>
                <h2 class="dialog-title" id="res-{{ $r->id }}-title">{{ $r->user->name }}</h2>
                <p class="text-14" style="margin-top:-8px">{{ $r->user->location() }}</p>
                <dl class="dialog-grid">
                    <dt>Abholung</dt><dd>{{ $r->block()->pickup($settings) }}</dd>
                    <dt>Rückgabe</dt><dd>{{ $r->block()->returnInfo() }}</dd>
                    <dt>Status</dt><dd><x-status-tag :reservation="$r" :today="$today" /></dd>
                    @if ($r->note)
                        <dt>Notiz</dt><dd><em>{{ $r->note }}</em></dd>
                    @endif
                    @if ($r->coins_collected !== null)
                        <dt>Münzen</dt><dd>{{ $r->coins_collected }} eingesammelt · {{ \App\Support\Fmt::eur($r->coins_collected * $settings->coin_price) }}</dd>
                    @endif
                </dl>

                @if ($r->status === \App\Enums\ReservationStatus::Returned && $r->coins_collected === null)
                    <form method="POST" action="{{ route('admin.reservations.coins', $r) }}" style="display:flex;gap:10px;align-items:flex-end">
                        @csrf
                        @method('PATCH')
                        <div class="field" style="width:160px">
                            <label for="coins-{{ $r->id }}">Eingesammelte Münzen</label>
                            <input id="coins-{{ $r->id }}" class="input" type="number" name="coins" min="0" max="99" inputmode="numeric" required>
                        </div>
                        <button type="submit" class="btn btn-secondary">Erfassen</button>
                    </form>
                @endif

                <div class="dialog-actions">
                    @if ($r->status === \App\Enums\ReservationStatus::Reserved)
                        <span style="margin-right:auto">
                            <x-confirm id="cancel-{{ $r->id }}" trigger="Stornieren" trigger-class="btn btn-ghost"
                                       title="Reservierung stornieren?"
                                       body="{{ $r->user->name }} · {{ $r->when() }} · Sauna {{ $r->saunaLabel() }}. Die Partei sieht den Termin als abgesagt."
                                       :action="route('admin.reservations.status', $r)" method="PATCH" confirm="Stornieren">
                                <input type="hidden" name="status" value="abgesagt">
                            </x-confirm>
                        </span>
                    @endif
                    <button type="button" class="btn btn-secondary" data-dialog-close>Schließen</button>
                    @foreach ($r->adminActions() as [$status, $label, $class])
                        <form method="POST" action="{{ route('admin.reservations.status', $r) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit" class="btn {{ $class }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </dialog>
        @endforeach
    </div>
</x-app-layout>
