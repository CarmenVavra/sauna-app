import Alpine from 'alpinejs';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const eur = (n) => (Math.round(n * 100) / 100).toFixed(2).replace('.', ',') + ' €';

window.toast = (message) => window.dispatchEvent(new CustomEvent('toast', { detail: message }));

/* Native Dialoge: Öffnen per [data-dialog-open], Schließen per [data-dialog-close] oder Klick auf den Hintergrund. */
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        document.getElementById(opener.dataset.dialogOpen)?.showModal();
        return;
    }
    const closer = event.target.closest('[data-dialog-close]');
    if (closer) {
        closer.closest('dialog')?.close();
        return;
    }
    if (event.target instanceof HTMLDialogElement && event.target.open) {
        event.target.close();
    }
});

/* Formulare nur einmal absenden. Ein zweites Absenden (Doppelklick, Enter + Klick) käme mit der
   Sitzung von vor dem ersten Request an – nach dem Login ist die verworfen, Ergebnis wäre 419 „Page Expired“.
   Nach 10 s wird wieder freigegeben, falls der Server nicht antwortet. */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !(form instanceof HTMLFormElement) || form.method === 'dialog') return;
    if (form.dataset.submitting) {
        event.preventDefault();
        return;
    }
    form.dataset.submitting = '1';
    form.setAttribute('aria-busy', 'true');
    setTimeout(() => releaseForm(form), 10000);
});
const releaseForm = (form) => {
    delete form.dataset.submitting;
    form.removeAttribute('aria-busy');
};
/* Zurück-Taste: Seite kommt aus dem Cache des Browsers, Formulare wieder freigeben. */
window.addEventListener('pageshow', (event) => {
    if (event.persisted) document.querySelectorAll('form[data-submitting]').forEach(releaseForm);
});

/* Toast unten links, 4,2 s sichtbar. */
Alpine.data('toast', (initial = '') => ({
    message: initial,
    visible: false,
    timer: null,
    init() {
        if (this.message) this.show(this.message);
        window.addEventListener('toast', (e) => this.show(e.detail));
    },
    show(message) {
        this.message = message;
        this.visible = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.visible = false), 4200);
    },
}));

/* „Kopieren“-Schaltfläche (z. B. Zugangsdaten). */
Alpine.data('copyText', (text) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2500);
        } catch {
            window.toast('Kopieren nicht möglich – bitte abschreiben.');
        }
    },
}));

/* Live-Summe „2 × 5,00 € = 10,00 €“ für Münzverkauf und -kauf. */
Alpine.data('liveSum', (qty = 2, price = 5) => ({
    qty,
    price,
    get sum() {
        const q = parseInt(this.qty, 10) || 0;
        const p = parseFloat(String(this.price).replace(',', '.')) || 0;
        return q > 0 ? `${q} × ${eur(p)} = ${eur(q * p)}` : '';
    },
}));

/* Dialoge „Partei registrieren“ / „Partei ändern“: Vorschau des Benutzernamens.
   Beim Ändern zählt der bisherige Benutzername der Partei nicht als vergeben. */
Alpine.data('partyForm', (allTaken = [], first = '', last = '', own = null, rename = false) => ({
    first,
    last,
    rename,
    get taken() {
        return own ? allTaken.filter((u) => u !== own) : allTaken;
    },
    get ok() {
        return this.first.trim() !== '' && this.last.trim() !== '';
    },
    get username() {
        if (!this.ok) return 'vorname.nachname';
        const base = (this.first.trim() + '.' + this.last.trim())
            .toLowerCase()
            .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
            .replace(/[^a-z.]/g, '');
        let u = base;
        for (let k = 2; this.taken.includes(u); k++) u = base + k;
        return u;
    },
}));

/* Parteisuche: Text ohne Akzente/Groß-Klein vergleichen. */
const fold = (s) =>
    String(s).toLowerCase().replace(/ß/g, 'ss').normalize('NFD').replace(/[̀-ͯ]/g, '');

/* Suchbegriff zerlegen: „3 7“ / „3/7“ = Stiege 3, Top 7; „st3“, „stiege 3“, „top 7“, „t7“ gezielt; Rest = Namensteile. */
const parsePartyQuery = (query) => {
    const tokens = fold(query).split(/[\s/,;·–-]+/).filter(Boolean);
    const isStair = (t) => t.length >= 2 && 'stiege'.startsWith(t);
    const isTop = (t) => 'top'.startsWith(t);
    const q = { staircase: null, apartment: null, numbers: [], words: [] };
    for (let i = 0; i < tokens.length; i++) {
        const t = tokens[i];
        const next = tokens[i + 1];
        let m;
        if ((m = t.match(/^(?:stiege|st|s)(\d+)$/))) q.staircase = Number(m[1]);
        else if ((m = t.match(/^(?:top|t)(\d+)$/))) q.apartment = Number(m[1]);
        else if (isStair(t) && /^\d+$/.test(next ?? '')) q.staircase = Number(tokens[++i]);
        else if (isTop(t) && /^\d+$/.test(next ?? '')) q.apartment = Number(tokens[++i]);
        else if (/^\d+$/.test(t)) q.numbers.push(Number(t));
        else if (next === undefined && (t === 'stiege' || t === 'top')) continue; // Zahl folgt noch
        else q.words.push(t);
    }
    if (q.staircase === null && q.apartment === null && q.numbers.length >= 2) {
        [q.staircase, q.apartment] = q.numbers.splice(0, 2);
    } else if (q.staircase !== null && q.apartment === null && q.numbers.length) {
        q.apartment = q.numbers.shift();
    } else if (q.apartment !== null && q.staircase === null && q.numbers.length) {
        q.staircase = q.numbers.shift();
    }
    return q;
};

const matchesParty = (option, q) =>
    (q.staircase === null || option.staircase === q.staircase) &&
    (q.apartment === null || option.apartment === q.apartment) &&
    q.numbers.every((n) => option.staircase === n || option.apartment === n) &&
    q.words.every((w) => fold(option.words).includes(w));

/* Combobox „Partei“ (Münzverkauf): tippen filtert, Pfeiltasten wählen, Enter übernimmt. */
Alpine.data('partyPicker', (options = [], selected = null, label = '') => ({
    options,
    selected: selected === null ? null : Number(selected),
    selectedLabel: label,
    query: label,
    results: options,
    open: false,
    active: -1,
    status: '',
    get expanded() {
        return this.open && this.results.length > 0;
    },
    filter() {
        if (this.query.trim() === '' || this.query === this.selectedLabel) return this.options;
        const q = parsePartyQuery(this.query);
        return this.options.filter((o) => matchesParty(o, q));
    },
    announce() {
        const n = this.results.length;
        this.status = n === 0 ? 'Keine Partei gefunden' : n === 1 ? '1 Partei gefunden' : `${n} Parteien gefunden`;
    },
    search() {
        if (this.query !== this.selectedLabel) this.selected = null;
        this.results = this.filter();
        this.open = true;
        this.active = this.results.length ? 0 : -1;
        this.announce();
    },
    openList() {
        if (this.open) return;
        this.results = this.filter();
        this.open = true;
        const current = this.results.findIndex((o) => o.id === this.selected);
        this.active = current >= 0 ? current : this.results.length ? 0 : -1;
        this.scrollToActive();
        this.announce();
    },
    close() {
        this.open = false;
    },
    move(step) {
        if (!this.open) return this.openList();
        const n = this.results.length;
        if (!n) return;
        this.active = (this.active + step + n) % n;
        this.scrollToActive();
    },
    scrollToActive() {
        this.$nextTick(() => {
            const option = this.results[this.active];
            if (option) document.getElementById(`${this.$refs.input.id}-opt-${option.id}`)?.scrollIntoView({ block: 'nearest' });
        });
    },
    choose(option) {
        this.selected = option.id;
        this.selectedLabel = option.label;
        this.query = option.label;
        this.close();
        this.status = `${option.label} ausgewählt`;
        this.$refs.input.focus();
    },
    enter(event) {
        if (!this.expanded || this.active < 0) return;
        event.preventDefault();
        this.choose(this.results[this.active]);
    },
    escape(event) {
        if (this.open) {
            event.preventDefault();
            this.close();
        } else if (this.query !== '') {
            event.preventDefault();
            this.clear();
        }
    },
    clear() {
        this.query = '';
        this.selected = null;
        this.selectedLabel = '';
        this.results = this.options;
        this.active = -1;
        this.status = 'Auswahl gelöscht';
        this.$refs.input.focus();
    },
}));

/* Zeile in „Meine Öle“: Marke/Notiz speichern beim Verlassen des Feldes,
   Wirkung aufklappen, bearbeiten und erst mit „Speichern“ übernehmen. */
Alpine.data('oilRow', (config) => ({
    open: false,
    editing: false,
    summary: config.summary,
    points: config.points,
    saved: config.effect ?? '',
    async save(form, extra = {}) {
        const body = new FormData(form);
        for (const [key, value] of Object.entries(extra)) body.append(key, value);
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body,
        });
        const data = await response.json().catch(() => ({}));
        window.toast(response.ok ? data.message : (data.message ?? 'Speichern fehlgeschlagen.'));
        if (response.ok) {
            this.summary = data.summary;
            this.points = data.points;
        }
        return response.ok;
    },
    edit() {
        this.editing = true;
        this.$nextTick(() => this.$refs.effect.focus());
    },
    async finish(form) {
        const effect = this.$refs.effect.value;
        if (await this.save(form, { effect })) {
            this.saved = effect;
            this.stopEditing();
        }
    },
    cancel() {
        this.$refs.effect.value = this.saved;
        this.stopEditing();
    },
    stopEditing() {
        this.editing = false;
        this.$nextTick(() => this.$refs.editButton.focus());
    },
}));

/* Liste „Meine Mischungen“: Namen auf-/zuklappen (mehrere gleichzeitig), „Alle öffnen/schließen“.
   Geöffnete Mischungen merkt sich der Browser; eine eben gespeicherte wird zusätzlich geöffnet. */
const BLENDS_OPEN_KEY = 'saunabuch.blends-open';
Alpine.data('blendAccordion', (ids = [], justSaved = null) => ({
    ids,
    open: [],
    init() {
        try {
            const saved = JSON.parse(localStorage.getItem(BLENDS_OPEN_KEY) ?? '[]');
            if (Array.isArray(saved)) this.open = saved.filter((id) => ids.includes(id));
        } catch {
            this.open = [];
        }
        if (justSaved !== null && ids.includes(justSaved) && !this.open.includes(justSaved)) {
            this.open.push(justSaved);
            this.persist();
        }
    },
    persist() {
        try {
            localStorage.setItem(BLENDS_OPEN_KEY, JSON.stringify(this.open));
        } catch {
            /* ohne Speicher einfach nicht merken */
        }
    },
    isOpen(id) {
        return this.open.includes(id);
    },
    toggle(id) {
        this.open = this.isOpen(id) ? this.open.filter((x) => x !== id) : [...this.open, id];
        this.persist();
    },
    openAll() {
        this.open = [...this.ids];
        this.persist();
    },
    closeAll() {
        this.open = [];
        this.persist();
    },
    get allOpen() {
        return this.ids.length > 0 && this.open.length === this.ids.length;
    },
    get noneOpen() {
        return this.open.length === 0;
    },
}));

/* Formular „Mischung“: Zeilen für Öle hinzufügen/entfernen, Tropfensumme mit Hinweis über 10 Tropfen. */
let blendRowKey = 0;
const blendRow = (r = {}) => ({ key: ++blendRowKey, name: r.name ?? '', drops: Number(r.drops) || 3, note: r.note ?? '' });
Alpine.data('blendForm', (rows = []) => ({
    rows: rows.length ? rows.map(blendRow) : [blendRow()],
    get total() {
        return this.rows.filter((r) => r.name.trim()).reduce((sum, r) => sum + (Number(r.drops) || 0), 0);
    },
    get tooStrong() {
        return this.total > 10;
    },
    get ok() {
        return this.rows.some((r) => r.name.trim());
    },
    add() {
        if (this.rows.length >= 8) return;
        this.rows.push(blendRow());
        this.$nextTick(() => this.$el.querySelector('.blend-row:last-of-type input')?.focus());
    },
    remove(i) {
        this.rows.splice(i, 1);
        if (!this.rows.length) this.rows.push(blendRow());
    },
}));

/* Reservieren: Sauna wählen, Tag im Monatskalender, Übersicht. */
Alpine.data('reservationForm', (config) => ({
    saunas: config.saunas,
    months: config.months,
    blocks: config.blocks,
    sauna: config.selection.sauna,
    date: config.selection.date,
    monthIndex: Math.max(0, config.months.findIndex((m) => m.key === config.selection.month)),
    weekdays: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
    get month() {
        return this.months[this.monthIndex];
    },
    get summary() {
        return this.date ? this.blocks[this.date] : null;
    },
    get saunaLabel() {
        return 'Stiege ' + this.sauna;
    },
    isFree(day, sauna = this.sauna) {
        return !day.past && !day.mine && !day.taken[sauna];
    },
    blockFree(block, sauna) {
        for (const m of this.months) {
            const d = m.days.find((x) => x.block === block);
            if (d) return this.isFree(d, sauna);
        }
        return false;
    },
    pickSauna(number) {
        if (this.date && !this.blockFree(this.date, number)) this.date = null;
        this.sauna = number;
    },
    pickDay(day) {
        this.date = day.block;
    },
    selected(day) {
        return this.date === day.block;
    },
    disabled(day) {
        return !this.selected(day) && !this.isFree(day);
    },
    sub(day) {
        if (this.selected(day)) return day.weekend ? 'Wochenende' : 'gewählt';
        if (day.mine) return 'Ihr Termin';
        if (day.taken[this.sauna]) return 'belegt';
        if (day.past) return '';
        return day.today ? 'heute' : 'frei';
    },
    dayLabel(day) {
        const state = this.sub(day) || 'vergangen';
        return `${day.n}. ${this.month.title}, ${state}`;
    },
}));

/* Saunatagebuch-Editor. */
Alpine.data('diaryEditor', (config) => ({
    coins: Number(config.draft.coins),
    temperature: Number(config.draft.temperature),
    sessions: Number(config.draft.sessions),
    infusion: Boolean(config.draft.infusion),
    oils: (config.draft.oils ?? []).map((o) => ({ name: o.name, drops: Number(o.drops) })),
    dosage: config.draft.dosage || 'passend',
    oilNames: config.oilNames,
    oilEffects: config.oilEffects ?? {},
    newOil: '',
    avgPrice: Number(config.draft.avgPrice),
    minutesPerCoin: Number(config.draft.minutesPerCoin),
    storeUrl: config.storeUrl,
    blends: config.blends ?? [],
    blendStoreUrl: config.blendStoreUrl,
    savingBlend: false,
    newBlendName: '',
    get cost() {
        return eur(this.coins * this.avgPrice);
    },
    get minutes() {
        return this.coins * this.minutesPerCoin + ' Min. Heizzeit';
    },
    get dropsTotal() {
        return 'Insgesamt ' + this.oils.reduce((a, o) => a + o.drops, 0) + ' Tropfen auf einen Kübel Wasser';
    },
    isOn(name) {
        return this.oils.some((o) => o.name === name);
    },
    effectOf(name) {
        const effect = this.oilEffects[name];
        return effect && effect.summary ? effect : null;
    },
    toggle(name) {
        this.oils = this.isOn(name) ? this.oils.filter((o) => o.name !== name) : [...this.oils, { name, drops: 3 }];
    },
    async addOil() {
        const name = this.newOil.trim();
        if (!name) return;
        const body = new FormData();
        body.append('name', name);
        body.append('select_existing', '1');
        const response = await fetch(this.storeUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            window.toast(data.message ?? 'Öl konnte nicht hinzugefügt werden.');
            return;
        }
        if (!this.oilNames.includes(data.name)) this.oilNames.push(data.name);
        this.oilEffects[data.name] = { summary: data.summary ?? '', points: data.points ?? [] };
        if (!this.isOn(data.name)) this.oils.push({ name: data.name, drops: 3 });
        this.newOil = '';
        window.toast(data.message);
    },
    /* Mischung, die genau der aktuellen Auswahl entspricht (gleiche Öle, gleiche Tropfen). */
    get activeBlend() {
        const key = (list) => list.map((o) => `${o.name.toLowerCase()}:${o.drops}`).sort().join('|');
        const current = key(this.oils);
        return current ? (this.blends.find((b) => key(b.ingredients) === current) ?? null) : null;
    },
    applyBlend(blend) {
        this.oils = blend.ingredients.map((i) => ({ name: i.name, drops: i.drops }));
        for (const i of blend.ingredients) if (!this.oilNames.includes(i.name)) this.oilNames.push(i.name);
        window.toast(`Mischung „${blend.name}“ übernommen.`);
    },
    startBlend() {
        this.savingBlend = true;
        this.$nextTick(() => this.$refs.blendName.focus());
    },
    async saveBlend() {
        const name = this.newBlendName.trim();
        if (!name || !this.oils.length) return;
        const response = await fetch(this.blendStoreUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ name, ingredients: this.oils.map((o) => ({ oil_name: o.name, drops: o.drops })) }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            window.toast(first ?? data.message ?? 'Mischung konnte nicht gespeichert werden.');
            return;
        }
        this.blends = [...this.blends, data.blend].sort((a, b) => a.name.localeCompare(b.name, 'de'));
        for (const n of data.addedOils ?? []) if (!this.oilNames.includes(n)) this.oilNames.push(n);
        this.newBlendName = '';
        this.savingBlend = false;
        window.toast(data.message);
    },
}));

window.Alpine = Alpine;
Alpine.start();
