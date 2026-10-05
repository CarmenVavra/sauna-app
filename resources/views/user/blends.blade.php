<x-app-layout title="Mischungen">
    <x-oil-tabs current="blends" />
    <x-kicker>Meine Mischungen</x-kicker>
    <h1 style="margin:0 0 12px">{{ $blends->count() }} {{ $blends->count() === 1 ? 'Mischung' : 'Mischungen' }}</h1>
    <p style="font-size:17px;margin:0 0 48px;max-width:58ch">Lieblingskombinationen für den Aufguss speichern und im Saunatagebuch mit einem Klick wiederverwenden.</p>

    <datalist id="blend-oil-names">
        @foreach ($oilNames as $name)
            <option value="{{ $name }}"></option>
        @endforeach
    </datalist>

    <div class="two-col">
        <section aria-labelledby="own-blends-title" x-data="blendAccordion(@js($blends->pluck('id')->all()), @js($openBlend))">
            <h2 class="sr-only" id="own-blends-title">Gespeicherte Mischungen</h2>
            @if ($blends->isNotEmpty())
                <div class="accordion-tools">
                    <button type="button" class="btn btn-ghost btn-small" @click="openAll()" :disabled="allOpen">Alle öffnen</button>
                    <button type="button" class="btn btn-ghost btn-small" @click="closeAll()" :disabled="noneOpen">Alle schließen</button>
                </div>
            @endif
            @forelse ($blends as $blend)
                <article class="blend-card" aria-labelledby="blend-{{ $blend->id }}-title">
                    <div class="blend-head">
                        {{-- Klick auf den Namen klappt Beschreibung und Öle auf; mehrere gleichzeitig möglich --}}
                        <h3 class="blend-title" id="blend-{{ $blend->id }}-title">
                            <button type="button" class="blend-toggle" @click="toggle({{ $blend->id }})" aria-expanded="false"
                                    :aria-expanded="isOpen({{ $blend->id }}).toString()" aria-controls="blend-{{ $blend->id }}-body">
                                <span class="blend-chevron" aria-hidden="true">▸</span><span>{{ $blend->name }}</span>
                            </button>
                        </h3>
                        <span class="btn-row" style="gap:2px">
                            <button type="button" class="btn btn-ghost btn-small" data-dialog-open="edit-blend-{{ $blend->id }}" aria-haspopup="dialog" aria-label="{{ $blend->name }} bearbeiten">Bearbeiten</button>
                            <x-confirm id="del-blend-{{ $blend->id }}" trigger="Löschen" aria-label="{{ $blend->name }} löschen"
                                       title="Mischung löschen?"
                                       body="„{{ $blend->name }}“ wird gelöscht. Ihre Öle und bisherigen Tagebucheinträge bleiben unverändert."
                                       :action="route('user.blends.destroy', $blend)" confirm="Löschen" />
                        </span>
                    </div>
                    <p class="blend-meta">
                        <span @class(['alert-text' => $blend->isTooStrong()])>{{ $blend->totalDrops() }} Tropfen</span> · {{ $blend->ingredients->pluck('oil_name')->join(', ') }}
                    </p>
                    <div class="blend-body" id="blend-{{ $blend->id }}-body" x-show="isOpen({{ $blend->id }})" x-cloak>
                    @if ($blend->description)
                        <p class="blend-desc">{{ $blend->description }}</p>
                    @endif
                    <ul class="blend-ingredients">
                        @foreach ($blend->ingredients as $i)
                            <li><span class="blend-drops">{{ $i->drops }} Tropfen</span> <span class="semibold">{{ $i->oil_name }}</span>@if ($i->note) <span class="muted">· {{ $i->note }}</span>@endif</li>
                        @endforeach
                    </ul>
                    <p class="blend-total @if ($blend->isTooStrong()) alert-text @endif">
                        Insgesamt {{ $blend->totalDrops() }} Tropfen auf 1 Liter Aufgusswasser @if ($blend->isTooStrong())– mehr als die empfohlenen 5 bis 10 Tropfen @endif
                    </p>
                    </div>

                    <dialog id="edit-blend-{{ $blend->id }}" class="dialog dialog-wide" aria-labelledby="edit-blend-{{ $blend->id }}-title"
                            @if ((string) old('blend_form') === (string) $blend->id && $errors->any()) x-data x-init="$el.showModal()" @endif>
                        <h2 class="dialog-title" id="edit-blend-{{ $blend->id }}-title">Mischung bearbeiten</h2>
                        <x-blend-form :key="$blend->id" :action="route('user.blends.update', $blend)" method="PUT" :blend="$blend" submit="Speichern" cancel />
                    </dialog>
                </article>
            @empty
                <div class="blend-card">
                    <p class="mb-0">Noch keine Mischungen. Legen Sie rechts Ihre erste an – oder speichern Sie im Tagebuch eine Auswahl als Mischung.</p>
                </div>
            @endforelse
        </section>

        <div style="max-width:440px">
            <section aria-labelledby="new-blend-title">
                <h2 class="h3" id="new-blend-title" style="margin-bottom:16px">Neue Mischung</h2>
                <x-blend-form key="new" :action="route('user.blends.store')" submit="Mischung speichern" />
                <p class="hint" style="margin-top:12px">Öle, die noch nicht in „Meine Öle“ stehen, werden dort automatisch ergänzt.</p>
            </section>
        </div>
    </div>
</x-app-layout>
