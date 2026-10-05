@props(['party', 'apartments', 'takenUsernames'])
{{-- „Ändern“ einer Partei (z. B. Namenswechsel nach Heirat) mit Bearbeitungsdialog. --}}
@php($failed = (string) old('edit_party') === (string) $party->id)
<button type="button" class="btn btn-ghost btn-small" data-dialog-open="edit-{{ $party->id }}" aria-haspopup="dialog" aria-label="{{ $party->name }} ändern">Ändern</button>
<dialog id="edit-{{ $party->id }}" class="dialog" aria-labelledby="edit-{{ $party->id }}-title"
        x-data="partyForm(@js($takenUsernames), @js($failed ? old('first_name') : $party->first_name), @js($failed ? old('last_name') : $party->last_name), @js($party->username), @js($failed && old('update_username')))"
        @if ($failed && $errors->any()) x-init="$el.showModal()" @endif>
    <form method="POST" action="{{ route('admin.parties.update', $party) }}" style="display:flex;flex-direction:column;gap:15px" novalidate>
        @csrf
        @method('PATCH')
        <input type="hidden" name="edit_party" value="{{ $party->id }}">
        <p class="kicker mb-0">Stiege {{ $party->staircase_number }} · Top {{ $party->apartment }}</p>
        <h2 class="dialog-title" id="edit-{{ $party->id }}-title">Partei ändern</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(180px,100%),1fr));gap:14px">
            <div class="field">
                <label for="edit-{{ $party->id }}-first">Vorname</label>
                <input id="edit-{{ $party->id }}-first" name="first_name" class="input" x-model="first" required autocomplete="off"
                       @if ($failed) @error('first_name') aria-invalid="true" @enderror @endif>
                @if ($failed) @error('first_name')<p class="error">{{ $message }}</p>@enderror @endif
            </div>
            <div class="field">
                <label for="edit-{{ $party->id }}-last">Nachname</label>
                <input id="edit-{{ $party->id }}-last" name="last_name" class="input" x-model="last" required autocomplete="off"
                       @if ($failed) @error('last_name') aria-invalid="true" @enderror @endif>
                @if ($failed) @error('last_name')<p class="error">{{ $message }}</p>@enderror @endif
            </div>
            <div class="field">
                <label for="edit-{{ $party->id }}-apartment">Wohnung</label>
                <select id="edit-{{ $party->id }}-apartment" name="apartment" class="input">
                    @for ($top = 1; $top <= $apartments; $top++)
                        <option value="{{ $top }}" @selected((int) ($failed ? old('apartment') : $party->apartment) === $top)>Top {{ $top }}</option>
                    @endfor
                </select>
                @if ($failed) @error('apartment')<p class="error">{{ $message }}</p>@enderror @endif
            </div>
        </div>
        <p class="text-14 mb-0">Benutzername: <strong>{{ $party->username }}</strong></p>
        <label class="text-14" style="display:inline-flex;gap:8px;align-items:center">
            <input type="checkbox" name="update_username" value="1" x-model="rename">
            Benutzername an den neuen Namen anpassen
        </label>
        <p class="text-14 mb-0" x-show="rename" x-cloak aria-live="polite">Neuer Benutzername: <strong x-text="username"></strong></p>
        <p class="text-13 mb-0" style="color:color-mix(in srgb, var(--color-text) 65%, transparent)">Passwort, Saunagänge, Tagebuch und Münzen bleiben unverändert. Einen neuen Benutzernamen teilen Sie der Partei bitte mit.</p>
        <div class="dialog-actions">
            <button type="button" class="btn btn-secondary" data-dialog-close>Abbrechen</button>
            <button type="submit" class="btn btn-primary" :disabled="!ok">Speichern</button>
        </div>
    </form>
</dialog>
