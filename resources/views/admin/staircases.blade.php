<x-app-layout :title="'Stiege '.$staircase->number">
    <div class="master-detail">
        <div>
            <x-kicker>Wohnanlage</x-kicker>
            <h2 style="margin:0 0 8px">{{ $staircases->count() }} Stiegen</h2>
            <p class="text-14 muted" style="margin:0 0 24px">{{ $totals }}</p>
            <nav aria-label="Stiegen">
                <ul class="side-list">
                    @foreach ($staircases as $s)
                        <li>
                            <a class="side-item" href="{{ route('admin.staircases.show', $s) }}" @if ($s->is($staircase)) aria-current="page" @endif>
                                <span>
                                    <span class="side-title">Stiege {{ $s->number }}</span>
                                    <span class="side-sub">{{ $s->apartments }} Wohnungen · {{ $s->parties_count }} Parteien</span>
                                </span>
                                @if ($s->has_sauna)
                                    <span class="tag tag-accent">Sauna</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div x-data="partyForm(@js($takenUsernames))">
            <div class="page-head" style="margin-bottom:36px">
                <div>
                    <x-kicker>Stiege bearbeiten</x-kicker>
                    <h1 class="mb-0">Stiege {{ $staircase->number }}</h1>
                </div>
                <button type="button" class="btn btn-primary" aria-haspopup="dialog"
                        @click="$refs.apartment.value = '1'; $refs.partyDialog.showModal()">Partei registrieren</button>
            </div>

            <form method="POST" action="{{ route('admin.staircases.update', $staircase) }}" style="display:flex;gap:24px 48px;align-items:flex-end;margin-bottom:52px;flex-wrap:wrap" novalidate>
                @csrf
                @method('PATCH')
                <div class="field" style="width:150px;flex:none">
                    <label for="apartments">Anzahl Wohnungen</label>
                    <input id="apartments" name="apartments" class="input" type="number" min="1" max="40" value="{{ old('apartments', $staircase->apartments) }}" required
                           @error('apartments') aria-invalid="true" aria-describedby="apartments-error" @enderror>
                </div>
                <fieldset class="field" style="flex:none">
                    <legend>Sauna im Dachgeschoß</legend>
                    <div class="seg">
                        <label class="seg-opt"><input type="radio" name="has_sauna" value="1" @checked(old('has_sauna', $staircase->has_sauna ? '1' : '0') === '1')>Ja</label>
                        <label class="seg-opt"><input type="radio" name="has_sauna" value="0" @checked(old('has_sauna', $staircase->has_sauna ? '1' : '0') === '0')>Nein</label>
                    </div>
                </fieldset>
                <div class="field" style="flex:none">
                    <span class="label">Registrierte Parteien</span>
                    <div style="font-size:22px;font-weight:600;line-height:36px">{{ $parties->flatten()->count() }}</div>
                </div>
                <button type="submit" class="btn btn-secondary">Speichern</button>
                @error('apartments')<p id="apartments-error" class="error" style="flex-basis:100%">{{ $message }}</p>@enderror
                @error('has_sauna')
                    <div style="flex-basis:100%">
                        <p class="error" role="alert">{{ $message }}</p>
                        <label class="text-14" style="display:inline-flex;gap:8px;align-items:center;margin-top:6px">
                            <input type="checkbox" name="confirm_cancel" value="1"> Trotzdem deaktivieren und Reservierungen stornieren
                        </label>
                    </div>
                @enderror
            </form>

            <div class="list-head cols-flats" style="padding:0 0 8px"><span>Wohnung</span><span>Parteien · Benutzername</span><span></span></div>
            <ul class="list">
                @for ($top = 1; $top <= $staircase->apartments; $top++)
                    <li class="list-row cols-flats">
                        <span class="semibold" style="line-height:28px">Top {{ $top }}</span>
                        <div style="display:flex;flex-direction:column;gap:2px">
                            @forelse ($parties->get($top, collect()) as $p)
                                <div class="cols-party">
                                    <span>{{ $p->name }}</span>
                                    <span class="text-13 muted">{{ $p->username }}</span>
                                    <span class="btn-row" style="gap:2px">
                                        <x-party-edit :party="$p" :apartments="$staircase->apartments" :taken-usernames="$takenUsernames" />
                                        <x-confirm id="pw-{{ $p->id }}" trigger="Passwort" aria-label="Neues Startpasswort für {{ $p->name }}"
                                                   title="Passwort zurücksetzen?"
                                                   body="{{ $p->name }} erhält ein neues Startpasswort und muss es beim nächsten Login ändern."
                                                   :action="route('admin.parties.password', $p)" method="POST" confirm="Zurücksetzen" />
                                        <x-confirm id="rm-{{ $p->id }}" trigger="Entfernen" aria-label="{{ $p->name }} entfernen"
                                                   title="Partei entfernen?"
                                                   body="{{ $p->name }} (Stiege {{ $p->staircase_number }}, Top {{ $p->apartment }}) verliert den Zugang. Vergangene Saunagänge bleiben in der Abrechnung erhalten."
                                                   :action="route('admin.parties.destroy', $p)" confirm="Entfernen" />
                                    </span>
                                </div>
                            @empty
                                <span class="text-14" style="line-height:28px;color:color-mix(in srgb, var(--color-text) 55%, transparent)">Keine Partei registriert</span>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-ghost row-action" style="justify-self:end" aria-haspopup="dialog" aria-label="Partei in Top {{ $top }} registrieren"
                                @click="$refs.apartment.value = '{{ $top }}'; $refs.partyDialog.showModal()">+ Partei</button>
                    </li>
                @endfor
            </ul>

            {{-- Dialog „Partei registrieren“ --}}
            <dialog x-ref="partyDialog" id="party-dialog" class="dialog" aria-labelledby="party-title" @if (! old('edit_party') && $errors->hasAny(['first_name', 'last_name', 'apartment', 'password'])) x-init="$el.showModal()" @endif>
                <form method="POST" action="{{ route('admin.parties.store', $staircase) }}" style="display:flex;flex-direction:column;gap:15px" novalidate>
                    @csrf
                    <p class="kicker mb-0">Stiege {{ $staircase->number }}</p>
                    <h2 class="dialog-title" id="party-title">Partei registrieren</h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(180px,100%),1fr));gap:14px">
                        <div class="field">
                            <label for="first_name">Vorname</label>
                            <input id="first_name" name="first_name" class="input" x-model="first" x-init="first = @js(old('edit_party') ? '' : old('first_name', ''))" required autocomplete="off">
                            @error('first_name')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="last_name">Nachname</label>
                            <input id="last_name" name="last_name" class="input" x-model="last" x-init="last = @js(old('edit_party') ? '' : old('last_name', ''))" required autocomplete="off">
                            @error('last_name')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="apartment">Wohnung</label>
                            <select id="apartment" name="apartment" class="input" x-ref="apartment">
                                @for ($top = 1; $top <= $staircase->apartments; $top++)
                                    <option value="{{ $top }}" @selected((int) old('apartment', 1) === $top)>Top {{ $top }}</option>
                                @endfor
                            </select>
                            @error('apartment')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="start_password">Startpasswort</label>
                            <input id="start_password" name="password" class="input" value="{{ old('password', $startPassword) }}" readonly>
                        </div>
                    </div>
                    <p class="text-14 mb-0" aria-live="polite">Benutzername: <strong x-text="username">vorname.nachname</strong></p>
                    <p class="text-13 mb-0" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Die Zugangsdaten übergeben Sie der Partei persönlich. Beim ersten Login wird ein neues Passwort gesetzt.</p>
                    <div class="dialog-actions">
                        <button type="button" class="btn btn-secondary" data-dialog-close>Abbrechen</button>
                        <button type="submit" class="btn btn-primary" :disabled="!ok">Zugang anlegen</button>
                    </div>
                </form>
            </dialog>
        </div>
    </div>

    @if ($credentials)
        <x-credentials-dialog :credentials="$credentials" />
    @endif
</x-app-layout>
