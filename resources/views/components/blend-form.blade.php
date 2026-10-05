@props(['key', 'action', 'method' => 'POST', 'blend' => null, 'submit' => 'Speichern', 'cancel' => false])
{{-- Formular für eine Mischung: Name, Anlass/Beschreibung und bis zu 8 Öle mit Tropfen und Hinweis. --}}
@php
    $failed = (string) old('blend_form') === (string) $key;
    $rows = $failed ? array_values((array) old('ingredients', [])) : ($blend?->toEditorArray()['ingredients'] ?? []);
    $rows = array_map(fn ($r) => ['name' => $r['name'] ?? $r['oil_name'] ?? '', 'drops' => $r['drops'] ?? 3, 'note' => $r['note'] ?? ''], $rows);
@endphp
<form method="POST" action="{{ $action }}" class="blend-form" novalidate x-data="blendForm(@js($rows))">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <input type="hidden" name="blend_form" value="{{ $key }}">

    <div class="field">
        <label for="{{ $key }}-name">Name der Mischung</label>
        <input id="{{ $key }}-name" name="name" class="input" maxlength="60" required placeholder="z. B. Freier Atem"
               value="{{ $failed ? old('name') : $blend?->name }}" @if ($failed) @error('name') aria-invalid="true" aria-describedby="{{ $key }}-name-error" @enderror @endif>
        @if ($failed) @error('name')<p id="{{ $key }}-name-error" class="error">{{ $message }}</p>@enderror @endif
    </div>
    <div class="field">
        <label for="{{ $key }}-description">Anlass &amp; Wirkung (optional)</label>
        <textarea id="{{ $key }}-description" name="description" class="input" rows="3" maxlength="1000" placeholder="z. B. Bei Erkältungszeit – öffnet die Atemwege.">{{ $failed ? old('description') : $blend?->description }}</textarea>
    </div>

    <fieldset class="field blend-rows">
        <legend>Öle</legend>
        <div class="blend-row blend-row-head" aria-hidden="true"><span>Öl</span><span>Tropfen</span><span>Hinweis (optional)</span><span></span></div>
        <template x-for="(row, i) in rows" :key="row.key">
            <div class="blend-row">
                <input class="input" list="blend-oil-names" maxlength="60" x-model="row.name" :name="'ingredients[' + i + '][oil_name]'"
                       :aria-label="'Öl ' + (i + 1)" placeholder="Öl wählen oder eingeben" autocomplete="off">
                <input class="input" type="number" min="1" max="20" inputmode="numeric" x-model.number="row.drops" :name="'ingredients[' + i + '][drops]'"
                       :aria-label="'Tropfen für ' + (row.name || 'Öl ' + (i + 1))">
                <input class="input" maxlength="80" x-model="row.note" :name="'ingredients[' + i + '][note]'"
                       :aria-label="'Hinweis zu ' + (row.name || 'Öl ' + (i + 1))" placeholder="z. B. oder Zitrone">
                <button type="button" class="btn btn-ghost btn-icon-sm" @click="remove(i)" :aria-label="'Zeile ' + (i + 1) + ' entfernen'">×</button>
            </div>
        </template>
        <button type="button" class="btn btn-secondary btn-small" style="align-self:flex-start" @click="add()" x-show="rows.length < 8">+ Weiteres Öl</button>
        @if ($failed)
            @foreach ($errors->get('ingredients*') as $messages)
                @foreach ($messages as $message)<p class="error">{{ $message }}</p>@endforeach
            @endforeach
        @endif
    </fieldset>

    <p class="text-14 mb-0" aria-live="polite" :class="{ 'alert-text': tooStrong }">
        <span x-text="'Insgesamt ' + total + ' Tropfen auf 1 Liter Aufgusswasser'"></span><span x-show="tooStrong"> – mehr als die empfohlenen 5 bis 10 Tropfen.</span>
    </p>

    <div class="btn-row">
        <button type="submit" class="btn btn-primary" :disabled="!ok">{{ $submit }}</button>
        @if ($cancel)
            <button type="button" class="btn btn-secondary" data-dialog-close>Abbrechen</button>
        @endif
    </div>
</form>
