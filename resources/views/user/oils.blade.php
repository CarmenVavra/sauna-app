<x-app-layout title="Öle">
    <x-oil-tabs current="oils" />
    <x-kicker>Meine Öle</x-kicker>
    <h1 style="margin:0 0 12px">{{ $oils->count() }} ätherische Öle</h1>
    <p style="font-size:17px;margin:0 0 32px;max-width:56ch">Diese Liste erscheint im Saunatagebuch zur Auswahl beim Aufguss. Marke und Notiz lassen sich direkt bearbeiten.</p>

    <aside class="notice" aria-labelledby="oil-safety-title">
        <h2 class="notice-title" id="oil-safety-title">Sicher aufgießen mit ätherischen Ölen</h2>
        <p>Ätherische Öle von doTERRA sind hochkonzentriert. Um sie in der Sauna sicher zu nutzen, sollten Sie folgende Regeln beachten:</p>
        <ol>
            <li><strong>Niemals pur auf die Steine:</strong> Geben Sie ätherische Öle niemals direkt auf die heißen Saunasteine, da sie hochentzündlich sind.</li>
            <li><strong>Richtig mischen:</strong> Mischen Sie für einen klassischen Aufguss etwa 5 bis 10 Tropfen ätherisches Öl in 1 Liter Aufgusswasser. Rühren Sie das Wasser vor dem Aufgießen gut um.</li>
        </ol>
    </aside>

    <div class="two-col">
        <div>
            <div class="list-head cols-oils"><span>Öl · Verwendung</span><span>Marke</span><span>Notiz</span><span></span></div>
            <ul class="list">
                @foreach ($oils as $oil)
                    @php($u = $usage[$oil->name] ?? null)
                    <li class="list-row compact cols-oils" x-data="oilRow(@js(['summary' => $oil->effectSummary(), 'points' => $oil->effectPoints(), 'effect' => $oil->effect]))">
                        <span class="block">
                            <span class="side-title">{{ $oil->name }}</span>
                            <span class="side-sub">{{ $u ? $u['times'].'× · '.$u['drops'].' Tropfen' : 'noch nicht verwendet' }}</span>
                        </span>
                        <form id="oil-{{ $oil->id }}" method="POST" action="{{ route('user.oils.update', $oil) }}" class="sr-only" @submit.prevent="save($el)">
                            @csrf
                            @method('PATCH')
                        </form>
                        <input form="oil-{{ $oil->id }}" name="brand" class="input" value="{{ $oil->brand }}" placeholder="Marke" maxlength="60"
                               aria-label="Marke für {{ $oil->name }}" @change="save(document.getElementById('oil-{{ $oil->id }}'))">
                        <input form="oil-{{ $oil->id }}" name="note" class="input" value="{{ $oil->note }}" placeholder="Notiz" maxlength="255"
                               aria-label="Notiz für {{ $oil->name }}" @change="save(document.getElementById('oil-{{ $oil->id }}'))">
                        <span class="row-action">
                            <x-confirm id="del-oil-{{ $oil->id }}" trigger="Entfernen" aria-label="{{ $oil->name }} entfernen"
                                       title="Öl entfernen?"
                                       body="{{ $oil->name }} verschwindet aus der Auswahl im Tagebuch. Bestehende Einträge bleiben unverändert."
                                       :action="route('user.oils.destroy', $oil)" confirm="Entfernen" />
                        </span>

                        {{-- Wirkung in der Sauna: aufklappbar, Kurzfassung aus den Titeln der Punkte --}}
                        <div class="oil-effect">
                            <button type="button" class="oil-effect-toggle" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="effect-{{ $oil->id }}">
                                <span class="oil-effect-label">Wirkung <span aria-hidden="true" x-text="open ? '▾' : '▸'">▸</span></span>
                                <span class="oil-effect-summary" x-text="summary || 'noch nicht erfasst – hinzufügen'">{{ $oil->effectSummary() ?: 'noch nicht erfasst – hinzufügen' }}</span>
                            </button>
                            <div id="effect-{{ $oil->id }}" class="oil-effect-panel" x-show="open" x-cloak>
                                <div x-show="!editing">
                                    <ul class="effect-points" x-show="points.length" aria-label="Wirkung von {{ $oil->name }}">
                                        <template x-for="(point, i) in points" :key="i">
                                            <li :class="{ 'is-plain': !point.bullet }"><strong x-show="point.title" x-text="point.title + ': '"></strong><span x-text="point.text"></span></li>
                                        </template>
                                    </ul>
                                    <p class="text-14 muted" x-show="!points.length">Noch keine Wirkung erfasst.</p>
                                    <button type="button" class="btn btn-secondary btn-small" x-ref="editButton" @click="edit()" aria-label="Wirkung von {{ $oil->name }} bearbeiten">Bearbeiten</button>
                                </div>
                                <div class="field" x-show="editing">
                                    <label for="effect-input-{{ $oil->id }}">Wirkung von {{ $oil->name }} in der Sauna</label>
                                    <textarea id="effect-input-{{ $oil->id }}" class="input effect-input" rows="9" maxlength="3000" x-ref="effect" @keydown.escape.prevent="cancel()"
                                              aria-describedby="effect-hint-{{ $oil->id }}">{{ $oil->effect }}</textarea>
                                    <p id="effect-hint-{{ $oil->id }}" class="hint">Eine Zeile pro Punkt, z. B. „• Freier Atem: Der Duft öffnet die Atemwege …“. Der Teil vor dem Doppelpunkt erscheint fett und in der Kurzfassung.</p>
                                    <div class="btn-row">
                                        <button type="button" class="btn btn-primary btn-small" @click="finish(document.getElementById('oil-{{ $oil->id }}'))">Speichern</button>
                                        <button type="button" class="btn btn-ghost btn-small" @click="cancel()">Abbrechen</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <form method="POST" action="{{ route('user.oils.store') }}" style="display:flex;flex-direction:column;gap:16px;max-width:380px" novalidate x-data="{ name: @js(old('name', '')) }">
            @csrf
            <h2 class="h3 mb-0">Öl hinzufügen</h2>
            <div class="field">
                <label for="oil-name">Name</label>
                <input id="oil-name" name="name" class="input" x-model="name" placeholder="z. B. Rosmarin" maxlength="60" required
                       @error('name') aria-invalid="true" aria-describedby="oil-name-error" @enderror>
                @error('name')<p id="oil-name-error" class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="oil-brand">Marke (optional)</label>
                <input id="oil-brand" name="brand" class="input" value="{{ old('brand') }}" maxlength="60">
            </div>
            <div class="field">
                <label for="oil-note">Notiz (optional)</label>
                <input id="oil-note" name="note" class="input" maxlength="255" placeholder="z. B. sparsam dosieren, passt zu Zitrone" value="{{ old('note') }}">
            </div>
            <div class="field">
                <label for="oil-effect">Wirkung in der Sauna (optional)</label>
                <textarea id="oil-effect" name="effect" class="input effect-input" rows="6" maxlength="3000" aria-describedby="oil-effect-hint"
                          placeholder="• Freier Atem: …&#10;• Entspannend: …">{{ old('effect') }}</textarea>
                <p id="oil-effect-hint" class="hint">Eine Zeile pro Punkt, Titel vor dem Doppelpunkt.</p>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-start" :disabled="!name.trim()">Hinzufügen</button>
        </form>
    </div>
</x-app-layout>
