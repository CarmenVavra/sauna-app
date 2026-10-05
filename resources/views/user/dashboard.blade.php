@php($eur = fn ($n) => \App\Support\Fmt::eur($n))
<x-app-layout title="Saunagänge">
    <div class="split">
        <div class="split-main">
            @foreach ($visits['openKeys'] as $o)
                <div style="margin-bottom:56px;max-width:640px" role="alert">
                    <p class="kicker kicker-alert" style="margin-bottom:10px">Schlüssel offen</p>
                    <p class="lead" style="margin:0 0 16px">Der Schlüssel für die Sauna {{ $o->saunaLabel() }} vom {{ $o->when() }} ist noch nicht zurückgemeldet.</p>
                    <form method="POST" action="{{ route('user.reservations.report', $o) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary">Schlüssel liegt im Postkasten</button>
                    </form>
                </div>
            @endforeach

            @if ($next = $visits['next'])
                <x-kicker>Nächster Saunagang</x-kicker>
                <h1 style="font-size:clamp(34px,6vw,54px);line-height:1.06;letter-spacing:-0.025em;max-width:17ch;margin:0 0 12px">{{ $next->whenLong() }}</h1>
                <p class="lead mb-0">Sauna {{ $next->saunaLabel() }} · Dachgeschoß</p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(180px,100%),1fr));gap:24px 36px;margin:36px 0 28px;max-width:780px">
                    <div>
                        <h2 class="h6">Schlüssel abholen</h2>
                        <p class="mb-0">{{ $next->status === \App\Enums\ReservationStatus::Reserved ? $next->block()->pickup($settings).' beim Hauswart' : 'abgeholt' }}</p>
                    </div>
                    <div>
                        <h2 class="h6">Schlüssel zurück</h2>
                        <p class="mb-0">{{ $next->block()->returnInfo() }}</p>
                    </div>
                    <div>
                        <h2 class="h6">Status</h2>
                        <x-status-tag :reservation="$next" :today="$today" />
                    </div>
                </div>
                @can('update', $next)
                    <div class="btn-row">
                        <a class="btn btn-secondary" href="{{ route('user.reservations.edit', $next) }}">Bearbeiten</a>
                        <x-confirm id="cancel-{{ $next->id }}" trigger="Absagen" trigger-class="btn btn-ghost"
                                   title="Saunagang absagen?"
                                   body="{{ $next->when() }}, Sauna {{ $next->saunaLabel() }}. Der Termin wird für andere Parteien wieder frei."
                                   :action="route('user.reservations.cancel', $next)" method="PATCH" confirm="Absagen" />
                    </div>
                @endcan
            @else
                <h1 class="display" style="margin:0 0 20px">Kein Saunagang geplant.</h1>
                <a class="btn btn-primary" href="{{ route('user.reservations.create') }}">Sauna reservieren</a>
            @endif

            @if ($visits['more']->isNotEmpty())
                <section class="section-lg" aria-labelledby="more-title">
                    <h2 class="h3" id="more-title">Weitere Termine</h2>
                    <ul class="list">
                        @foreach ($visits['more'] as $r)
                            <li class="list-row cols-more">
                                <span class="semibold">{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span class="text-13">Abholung {{ $r->block()->pickup($settings) }}</span>
                                <span class="btn-row row-action" style="gap:6px">
                                    @can('update', $r)
                                        <a class="btn btn-secondary" href="{{ route('user.reservations.edit', $r) }}" aria-label="{{ $r->when() }} bearbeiten">Bearbeiten</a>
                                        <x-confirm id="cancel-{{ $r->id }}" trigger="Absagen" trigger-class="btn btn-ghost" aria-label="{{ $r->when() }} absagen"
                                                   title="Saunagang absagen?"
                                                   body="{{ $r->when() }}, Sauna {{ $r->saunaLabel() }}. Der Termin wird für andere Parteien wieder frei."
                                                   :action="route('user.reservations.cancel', $r)" method="PATCH" confirm="Absagen" />
                                    @else
                                        <x-status-tag :reservation="$r" :today="$today" />
                                    @endcan
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="section-lg" aria-labelledby="past-title">
                <h2 class="h3" id="past-title">Vergangene Saunagänge</h2>
                @if ($visits['past']->isEmpty())
                    <p class="muted">Noch keine Saunagänge.</p>
                @else
                    <div class="list-head cols-past"><span>Termin</span><span>Sauna</span><span>Temp.</span><span>Aufguss</span><span class="right">Münzen</span><span class="right">Kosten</span><span></span></div>
                    <ul class="list">
                        @foreach ($visits['past'] as $r)
                            @php($e = $r->diaryEntry)
                            <li class="list-row compact cols-past">
                                <span>{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span data-label="Temp.">{{ $e ? $e->temperature.' °C' : '—' }}</span>
                                <span data-label="Aufguss">{{ $e ? ($e->infusion ? ($e->oils->pluck('name')->join(', ') ?: 'ja') : '—') : '—' }}</span>
                                <span class="right" data-label="Münzen">{{ $e ? $e->coins : '—' }}</span>
                                <span class="right" data-label="Kosten">{{ $e ? $eur($e->coins * $coins['avgPrice']) : '—' }}</span>
                                <span class="right row-action">
                                    <a class="btn btn-ghost btn-small" href="{{ route('user.diary', $r) }}" aria-label="{{ $e ? 'Tagebuch' : 'Eintrag schreiben' }}: {{ $r->when() }}">{{ $e ? 'Tagebuch' : 'Eintrag schreiben' }}</a>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($visits['cancelled']->isNotEmpty())
                <section style="margin-top:64px" aria-labelledby="cancelled-title">
                    <h2 class="h4" id="cancelled-title">Abgesagt</h2>
                    <ul class="list">
                        @foreach ($visits['cancelled'] as $r)
                            <li class="list-row compact cols-cancelled muted">
                                <span class="strike"><span class="sr-only">Abgesagt: </span>{{ $r->when() }}</span>
                                <span>{{ $r->saunaLabel() }}</span>
                                <span></span>
                                <span class="row-action">
                                    <x-confirm id="del-{{ $r->id }}" trigger="Löschen" aria-label="Abgesagten Termin {{ $r->when() }} löschen"
                                               title="Endgültig löschen?"
                                               body="Die abgesagte Reservierung {{ $r->when() }} wird aus Ihrer Liste entfernt."
                                               :action="route('user.reservations.destroy', $r)" confirm="Löschen" />
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="split-aside" aria-label="Münzen und Schlüsselausgabe">
            <x-kicker style="margin-bottom:34px">Münzen übrig</x-kicker>
            <x-plate-number :value="$coins['left']" />
            <p class="text-14" style="margin:22px 0 8px">{{ $coins['bought'] }} gekauft · {{ $coins['used'] }} verbraucht</p>
            @if ($coins['low'])
                <p class="text-14 alert-text" style="margin:0 0 8px">Beim nächsten Schlüssel gleich Münzen mitkaufen.</p>
            @endif
            <a class="text-14" href="{{ route('user.coins.index') }}">Münzkäufe ansehen</a>

            <h2 class="h4" style="margin:56px 0 10px">Schlüsselausgabe</h2>
            <p class="text-14" style="margin:0 0 10px">{{ $settings->monThuLabel() }}<br>{{ $settings->friLabel() }}</p>
            <p class="text-14" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Für Samstag und Sonntag den Schlüssel bis Freitag {{ $settings->fri_until }} abholen und Sonntagabend in den Postkasten neben der Wohnungstür des Hauswarts werfen.</p>
            <a class="btn btn-primary btn-block" href="{{ route('user.reservations.create') }}" style="margin-top:20px">Neue Reservierung</a>
        </aside>
    </div>
</x-app-layout>
