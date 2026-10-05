<x-app-layout title="Tagebuch">
    <div class="master-detail">
        <div>
            <x-kicker>Saunatagebuch</x-kicker>
            <h2 style="margin:0 0 24px">{{ $entryCount }} {{ $entryCount === 1 ? 'Eintrag' : 'Einträge' }}</h2>
            <nav aria-label="Vergangene Saunagänge">
                <ul class="side-list">
                    @foreach ($past as $r)
                        <li>
                            <a class="side-item" style="display:block" href="{{ route('user.diary', $r) }}" @if ($selected?->is($r)) aria-current="page" @endif>
                                <span class="side-title">{{ $r->when() }} · St. {{ $r->staircase_number }}</span>
                                @if ($e = $r->diaryEntry)
                                    <span class="side-sub">{{ $e->temperature }} °C · {{ $e->infusion ? 'Aufguss' : 'ohne Aufguss' }} · {{ $e->coins }} {{ $e->coins === 1 ? 'Münze' : 'Münzen' }}</span>
                                @else
                                    <span class="side-sub alert-text">Noch kein Eintrag</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div style="max-width:760px">
            @if (! $selected)
                <h1>Noch keine Saunagänge</h1>
                <p class="muted">Nach Ihrem ersten Saunagang können Sie hier Temperatur, Aufguss und Öle festhalten.</p>
            @else
                <x-kicker>{{ $entry ? 'Eintrag' : 'Neuer Eintrag' }}</x-kicker>
                <h1 style="margin:0 0 6px">{{ $selected->whenLong() }}</h1>
                <p style="font-size:18px;margin:0 0 48px">Sauna {{ $selected->saunaLabel() }}</p>

                <form method="POST" action="{{ route('user.diary.update', $selected) }}" novalidate
                      x-data="diaryEditor(@js(['draft' => $draft, 'oilNames' => $oilNames, 'oilEffects' => $oilEffects, 'blends' => $blends, 'storeUrl' => route('user.oils.store'), 'blendStoreUrl' => route('user.blends.store')]))">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="coins" :value="coins">
                    <input type="hidden" name="sessions" :value="sessions">
                    <input type="hidden" name="infusion" :value="infusion ? 1 : 0">
                    <template x-if="infusion">
                        <div>
                            <template x-for="(oil, i) in oils" :key="oil.name">
                                <span>
                                    <input type="hidden" :name="`oils[${i}][name]`" :value="oil.name">
                                    <input type="hidden" :name="`oils[${i}][drops]`" :value="oil.drops">
                                </span>
                            </template>
                        </div>
                    </template>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(170px,100%),1fr));gap:40px;margin-bottom:48px">
                        <div>
                            <label class="h6 block" for="temperature">Temperatur</label>
                            <div class="big-temp" aria-hidden="true"><span x-text="temperature">{{ $draft['temperature'] }}</span> °C</div>
                            <input id="temperature" name="temperature" type="range" min="50" max="110" step="1" x-model.number="temperature" value="{{ $draft['temperature'] }}"
                                   :aria-valuetext="temperature + ' Grad Celsius'">
                        </div>
                        <div role="group" aria-labelledby="coins-label">
                            <h2 class="h6 mb-0" id="coins-label">Münzen</h2>
                            <div class="stepper">
                                <button type="button" class="btn btn-secondary btn-icon" @click="coins = Math.max(0, coins - 1)" aria-label="Eine Münze weniger">−</button>
                                <span class="stepper-value" x-text="coins" aria-live="polite">{{ $draft['coins'] }}</span>
                                <button type="button" class="btn btn-secondary btn-icon" @click="coins = Math.min(24, coins + 1)" aria-label="Eine Münze mehr">+</button>
                            </div>
                            <div class="text-13"><span x-text="cost"></span> · <span x-text="minutes"></span></div>
                        </div>
                        <div role="group" aria-labelledby="sessions-label">
                            <h2 class="h6 mb-0" id="sessions-label">Saunagänge</h2>
                            <div class="stepper">
                                <button type="button" class="btn btn-secondary btn-icon" @click="sessions = Math.max(1, sessions - 1)" aria-label="Ein Saunagang weniger">−</button>
                                <span class="stepper-value" x-text="sessions" aria-live="polite">{{ $draft['sessions'] }}</span>
                                <button type="button" class="btn btn-secondary btn-icon" @click="sessions = Math.min(12, sessions + 1)" aria-label="Ein Saunagang mehr">+</button>
                            </div>
                        </div>
                    </div>

                    <fieldset class="field" style="margin-bottom:28px">
                        <legend>Aufguss gemacht?</legend>
                        <div class="seg">
                            <label class="seg-opt"><input type="radio" name="infusion_choice" value="1" :checked="infusion" @change="infusion = true">Ja</label>
                            <label class="seg-opt"><input type="radio" name="infusion_choice" value="0" :checked="!infusion" @change="infusion = false">Nein</label>
                        </div>
                    </fieldset>

                    <div x-show="infusion" x-cloak style="margin-bottom:40px">
                        <h2 class="h6" style="margin-bottom:12px" id="oils-label">Ätherische Öle</h2>
                        <div x-show="blends.length" class="blend-picker">
                            <p class="label" id="blends-label">Mischung verwenden</p>
                            <div class="chips" role="group" aria-labelledby="blends-label">
                                <template x-for="blend in blends" :key="blend.id">
                                    <button type="button" class="btn btn-small" :class="activeBlend?.id === blend.id ? 'btn-primary' : 'btn-secondary'"
                                            :aria-pressed="(activeBlend?.id === blend.id).toString()" @click="applyBlend(blend)" x-text="blend.name"></button>
                                </template>
                            </div>
                            <p class="label">Einzelne Öle</p>
                        </div>
                        <div class="chips" role="group" aria-labelledby="oils-label">
                            <template x-for="name in oilNames" :key="name">
                                <button type="button" class="btn" :class="isOn(name) ? 'btn-primary' : 'btn-secondary'" :aria-pressed="isOn(name)" @click="toggle(name)" x-text="name"></button>
                            </template>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;max-width:460px;margin:-8px 0 28px;flex-wrap:wrap">
                            <label for="new-oil" class="sr-only">Anderes Öl</label>
                            <input id="new-oil" class="input" style="flex:1;min-width:160px" x-model="newOil" placeholder="Anderes Öl, z. B. Rosmarin" @keydown.enter.prevent="addOil()">
                            <button type="button" class="btn btn-secondary" @click="addOil()">Hinzufügen</button>
                            <a href="{{ route('user.oils.index') }}" class="text-13 nowrap">Meine Öle</a>
                            <a href="{{ route('user.blends.index') }}" class="text-13 nowrap">Mischungen</a>
                        </div>
                        <div x-show="oils.length" style="display:flex;flex-direction:column;margin-bottom:28px;max-width:460px">
                            <template x-for="oil in oils" :key="oil.name">
                                <div class="list-row cols-drops">
                                    <span class="semibold" x-text="oil.name"></span>
                                    <span style="display:flex;align-items:center;gap:8px">
                                        <button type="button" class="btn btn-secondary btn-icon btn-icon-sm" @click="oil.drops = Math.max(1, oil.drops - 1)" :aria-label="'Ein Tropfen weniger ' + oil.name">−</button>
                                        <span style="min-width:78px;text-align:center" x-text="oil.drops + ' Tropfen'" aria-live="polite"></span>
                                        <button type="button" class="btn btn-secondary btn-icon btn-icon-sm" @click="oil.drops = Math.min(50, oil.drops + 1)" :aria-label="'Ein Tropfen mehr ' + oil.name">+</button>
                                    </span>
                                    {{-- Wirkung aus „Meine Öle“: Kurzfassung, aufklappbar zum vollen Text --}}
                                    <template x-if="effectOf(oil.name)">
                                        <details class="diary-effect">
                                            <summary><span class="diary-effect-label">Wirkung</span> <span x-text="effectOf(oil.name).summary"></span></summary>
                                            <ul class="effect-points" :aria-label="'Wirkung von ' + oil.name">
                                                <template x-for="(point, i) in effectOf(oil.name).points" :key="i">
                                                    <li :class="{ 'is-plain': !point.bullet }"><strong x-show="point.title" x-text="point.title + ': '"></strong><span x-text="point.text"></span></li>
                                                </template>
                                            </ul>
                                        </details>
                                    </template>
                                </div>
                            </template>
                            <div class="text-13" style="padding-top:8px;color:color-mix(in srgb, var(--color-text) 65%, transparent)" x-text="dropsTotal"></div>
                            {{-- Aktuelle Auswahl als Mischung merken --}}
                            <div class="blend-save" x-show="!activeBlend">
                                <button type="button" class="btn btn-ghost btn-small" x-show="!savingBlend" @click="startBlend()">Als Mischung speichern</button>
                                <div class="blend-save-form" x-show="savingBlend" x-cloak>
                                    <label for="new-blend-name" class="sr-only">Name der neuen Mischung</label>
                                    <input id="new-blend-name" class="input" x-ref="blendName" x-model="newBlendName" maxlength="60" placeholder="Name, z. B. Freier Atem"
                                           @keydown.enter.prevent="saveBlend()" @keydown.escape.prevent="savingBlend = false">
                                    <button type="button" class="btn btn-secondary" @click="saveBlend()" :disabled="!newBlendName.trim()">Speichern</button>
                                    <button type="button" class="btn btn-ghost" @click="savingBlend = false">Abbrechen</button>
                                </div>
                            </div>
                            <p class="text-13 mb-0" style="padding-top:6px" x-show="activeBlend" x-text="activeBlend ? 'Mischung „' + activeBlend.name + '“' : ''"></p>
                        </div>
                        <fieldset class="field" style="margin-bottom:18px">
                            <legend>Dosierung</legend>
                            <div class="seg">
                                @foreach (\App\Enums\Dosage::cases() as $d)
                                    <label class="seg-opt"><input type="radio" name="dosage" value="{{ $d->value }}" x-model="dosage">{{ $d->value }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="field">
                            <label for="oil_note">Notiz zur Dosierung</label>
                            <textarea id="oil_note" name="oil_note" class="input" maxlength="500" placeholder="z. B. Pfefferminze nächstes Mal einen Tropfen weniger">{{ $draft['oilNote'] }}</textarea>
                        </div>
                    </div>

                    <div class="field" style="margin-bottom:24px">
                        <label for="note">Notiz zum Saunagang</label>
                        <textarea id="note" name="note" class="input" maxlength="2000" placeholder="Wie war es?">{{ $draft['note'] }}</textarea>
                    </div>
                    @if ($errors->any())
                        <p class="error" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <button type="submit" class="btn btn-primary" style="padding-inline:28px">Eintrag speichern</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
