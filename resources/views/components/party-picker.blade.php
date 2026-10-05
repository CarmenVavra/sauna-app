{{-- Combobox nach WAI-ARIA-Muster: tippen filtert, ↑/↓ wählt, Enter übernimmt, Esc schließt. --}}
<div class="field picker" x-data="partyPicker(@js($options), @js($selected), @js($initialLabel))" @click.outside="close()">
    <label for="{{ $id }}">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" :value="selected ?? ''" value="{{ $selected }}">
    <div class="picker-box">
        <input id="{{ $id }}" class="input picker-input" type="text" role="combobox" autocomplete="off" spellcheck="false"
               placeholder="Name, Stiege oder Top …" aria-autocomplete="list" aria-controls="{{ $id }}-list"
               aria-describedby="{{ $id }}-hint" :aria-expanded="expanded.toString()" aria-expanded="false"
               :aria-activedescendant="expanded && active >= 0 ? '{{ $id }}-opt-' + results[active].id : null"
               x-model="query" x-ref="input" value="{{ $initialLabel }}"
               @input="search()" @focus="$el.select()" @click="openList()"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter="enter($event)" @keydown.escape="escape($event)" @keydown.tab="close()"
               @if ($invalid) aria-invalid="true" @endif>
        <button type="button" class="picker-clear" x-show="query !== ''" x-cloak @click="clear()" aria-label="Auswahl löschen">×</button>
    </div>
    <p id="{{ $id }}-hint" class="sr-only">Zum Beispiel „Huber“, „3 7“ für Stiege 3 Top 7 oder „top 7“. Mit Pfeiltasten auswählen, Enter übernimmt.</p>
    <ul id="{{ $id }}-list" class="picker-list" role="listbox" aria-label="{{ $label }}" x-show="expanded" x-cloak>
        <template x-for="(option, i) in results" :key="option.id">
            <li role="option" class="picker-option" :id="'{{ $id }}-opt-' + option.id"
                :aria-selected="(option.id === selected).toString()" :class="{ 'is-active': i === active }"
                @mousedown.prevent @click="choose(option)" @mousemove="active = i" x-text="option.label"></li>
        </template>
    </ul>
    <p class="picker-list picker-empty" x-show="open && results.length === 0" x-cloak>Keine Partei gefunden</p>
    <p class="sr-only" aria-live="polite" x-text="status"></p>
</div>
