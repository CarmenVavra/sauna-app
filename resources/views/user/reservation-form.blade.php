<x-app-layout :title="$reservation ? 'Termin ändern' : 'Sauna reservieren'">
    <div x-data="reservationForm(@js(['saunas' => $availability['saunas'], 'months' => $availability['months'], 'blocks' => $availability['blocks'], 'selection' => $selection]))">
        <x-kicker>{{ $reservation ? 'Reservierung bearbeiten' : 'Neue Reservierung' }}</x-kicker>
        <h1 style="margin:0 0 48px">{{ $reservation ? 'Termin ändern' : 'Sauna reservieren' }}</h1>

        <form method="POST" action="{{ $reservation ? route('user.reservations.update', $reservation) : route('user.reservations.store') }}" class="res-layout" novalidate>
            @csrf
            @if ($reservation)
                @method('PUT')
            @endif
            <input type="hidden" name="date" :value="date ?? ''">

            <fieldset class="res-col-sauna" style="border:0;padding:0;margin:0;min-width:0">
                <legend class="h6" style="margin-bottom:12px;padding:0">1 · Sauna</legend>
                @foreach ($availability['saunas'] as $s)
                    <label class="radio sauna-option">
                        <input type="radio" name="staircase_number" value="{{ $s['number'] }}" @checked($selection['sauna'] === $s['number'])
                               @change="pickSauna({{ $s['number'] }})">
                        <span class="dot" style="margin-top:4px"></span>
                        <span class="block">
                            <span class="block semibold" style="font-size:16px;line-height:1.3">{{ $s['label'] }}</span>
                            <span class="block small muted" style="line-height:1.4">frei ab {{ $s['nextFree'] }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div class="res-col-day">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;flex-wrap:wrap">
                    <h2 class="h6 mb-0" id="day-title">2 · Tag</h2>
                    <div style="display:flex;align-items:center;gap:8px">
                        <button type="button" class="btn btn-secondary btn-icon" @click="monthIndex--" :disabled="monthIndex === 0" aria-label="Vorheriger Monat">‹</button>
                        <span class="semibold" style="min-width:150px;text-align:center" x-text="month.title" aria-live="polite"></span>
                        <button type="button" class="btn btn-secondary btn-icon" @click="monthIndex++" :disabled="monthIndex === months.length - 1" aria-label="Nächster Monat">›</button>
                    </div>
                </div>
                <div class="month-grid" role="group" aria-labelledby="day-title">
                    <template x-for="w in weekdays" :key="w">
                        <div class="month-wd" aria-hidden="true" x-text="w"></div>
                    </template>
                    <template x-for="i in month.offset" :key="'blank' + i">
                        <div></div>
                    </template>
                    <template x-for="day in month.days" :key="day.date">
                        <button type="button" class="btn day-btn" :class="selected(day) ? 'btn-primary' : 'btn-secondary'"
                                :disabled="disabled(day)" :aria-pressed="selected(day)" :aria-label="dayLabel(day)" @click="pickDay(day)">
                            <span class="day-n" x-text="day.n"></span>
                            <span class="day-sub" x-text="sub(day)"></span>
                        </button>
                    </template>
                </div>
                <p class="text-13 muted" style="margin-top:14px">Samstag und Sonntag werden gemeinsam reserviert. Eine Reservierung gilt den ganzen Tag.</p>
                <noscript><p class="error">Für die Tagesauswahl wird JavaScript benötigt.</p></noscript>
            </div>

            <div class="res-col-summary" aria-live="polite">
                <h2 class="h6" style="margin-bottom:12px">3 · Übersicht</h2>
                @error('date')<p class="error" role="alert" style="margin-bottom:12px">{{ $message }}</p>@enderror
                @error('staircase_number')<p class="error" role="alert" style="margin-bottom:12px">{{ $message }}</p>@enderror
                <template x-if="summary">
                    <div>
                        <h3 style="margin:0 0 6px" x-text="summary.whenLong"></h3>
                        <p style="margin:0 0 24px">Sauna <span x-text="saunaLabel"></span> · Dachgeschoß</p>
                        <div style="display:flex;flex-direction:column;gap:16px;margin-bottom:24px;font-size:14px">
                            <div><div class="small" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Schlüssel abholen</div><div class="semibold" x-text="summary.pick"></div></div>
                            <div><div class="small" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Schlüssel zurück</div><div x-text="summary.ret"></div></div>
                            <div><div class="small" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Münzen</div><div>{{ $settings->coinLabel() }}. Sie haben noch {{ $coins['left'] }}.</div></div>
                        </div>
                    </div>
                </template>
                <template x-if="!summary">
                    <p style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Wählen Sie eine Sauna und einen freien Tag.</p>
                </template>
                <div class="field" x-show="summary" style="margin-bottom:18px">
                    <label for="note">Notiz an den Hauswart (optional)</label>
                    <textarea id="note" name="note" class="input" maxlength="500" placeholder="z. B. Abholung erst um 17 Uhr">{{ $selection['note'] }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block" x-show="summary">{{ $reservation ? 'Änderung speichern' : 'Verbindlich reservieren' }}</button>
                <a class="btn btn-ghost" href="{{ route('user.dashboard') }}" style="margin-top:12px">Zurück zu meinen Saunagängen</a>
            </div>
        </form>
    </div>
</x-app-layout>
