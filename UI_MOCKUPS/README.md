# Handoff: Saunabuch – Sauna-Verwaltung für eine Wohnanlage

## Überblick
Web-App für eine Wohnanlage mit 15 Stiegen (Stiege 1–15). Einige Stiegen haben eine Sauna im Dachgeschoß (im Prototyp: 2, 5, 8, 11, 14). Parteien (Bewohner) reservieren Saunatage in **beliebiger** Stiege. Der Hauswart verwaltet Stiegen, Wohnungen, Parteien, Schlüssel und Sauna-Münzen.

Zwei Rollen mit Login:
- **Admin (Hauswart)** – registriert Parteien (keine Selbstregistrierung), pflegt Stammdaten, Schlüsselübergabe, Münzverkauf und -einsammlung.
- **User (Partei)** – reserviert, bearbeitet, sagt ab und löscht Saunagänge, führt Saunatagebuch, Münzkäufe, Öle, Jahresbilanz.

## Über die Design-Dateien
Die Dateien in `prototype/` sind **Design-Referenzen in HTML** – ein klickbarer Prototyp, der Aussehen und Verhalten zeigt, **kein Produktionscode**. Aufgabe ist, das Design in der Zielumgebung nachzubauen (bestehendes Projekt mit dessen Framework/Patterns, oder – falls noch keines existiert – ein passender Stack, z. B. React/Next.js oder SvelteKit + Postgres/SQLite, echte Auth mit gehashten Passwörtern).

Prototyp öffnen: `prototype/Saunabuch.dc.html` im Browser (über einen lokalen Webserver, z. B. `npx serve prototype`). Demo-Login: Buttons „Als Hauswart“ / „Als Maria Huber“ (oder Benutzername `hauswart` bzw. `maria.huber`, beliebiges Passwort). Alle Daten sind Mock-Daten im Speicher; „heute“ ist fest auf **Do, 1. Oktober 2026** gesetzt.

Die gesamte Logik (Datenmodell, Seed, Regeln, Berechnungen) steht im `<script data-dc-script>`-Block am Ende von `Saunabuch.dc.html` – gute Referenz für Business-Regeln.

## Fidelity
**High-fidelity.** Farben, Typografie, Abstände und Interaktionen sind final und sollen möglichst genau übernommen werden. Styling basiert auf dem Design-System **Broadsheet** (`prototype/_ds/.../styles.css`): Zeitungs-Ästhetik, alles in Source Serif 4, keine Boxen/Trennlinien zur Seitenstruktur, Hierarchie über Schriftgröße und Weißraum.

---

## Fachliche Regeln (wichtig)
1. **Reservierung = ganzer Tag**, eine Partei pro Sauna und Tag.
2. **Wochenende**: Samstag + Sonntag werden **gemeinsam als ein Block** reserviert (Annahme – mit Auftraggeber bestätigen). Schlüssel muss bis **Freitag, Ende Ausgabezeit (12:00)** abgeholt werden, Rückgabe Sonntagabend in den Postkasten. Wochenend-Reservierung ist nicht mehr möglich, wenn der Freitag vor dem Wochenende in der Vergangenheit liegt.
3. **Schlüsselausgabe-Zeiten** (konfigurierbar): Mo–Do 08:00–18:00, Fr 08:00–12:00. Kein Ausgabe am Wochenende.
4. **Rückgabe** am Abend des (letzten) Reservierungstags in den Postkasten neben der Wohnungstür des Hauswarts.
5. **Münzen**: 1 Münze = 60 Min. Sauna = 5,00 € (beides konfigurierbar). Gesamtbestand (z. B. 400) konfigurierbar. Münzen sind physisch und werden recycelt: Hauswart sammelt sie täglich ein und verkauft sie wieder.
   - Bei Parteien im Umlauf = verkauft − eingesammelt
   - Im Lager = Gesamtbestand − im Umlauf
   - Da eine Reservierung den ganzen Tag gilt, sind eingesammelte Münzen eindeutig **einer Reservierung/Partei** zuordenbar → „übrige Münzen je Partei“ = gekauft − verwendet.
6. Eine Partei kann nicht zwei Reservierungen am selben Tag haben.

### Schlüssel-Status (Reservierung)
```
reserviert ──(Admin: ausgegeben)──▶ ausgegeben ──(User: „liegt im Postkasten“)──▶ gemeldet ──(Admin: bestätigt)──▶ zurueck
                                         └───────────(Admin direkt: im Postkasten)───────────────────────────────▶ zurueck
gemeldet ──(Admin: „nicht im Postkasten“)──▶ ausgegeben
reserviert ──(User absagen / Admin stornieren)──▶ abgesagt ──(User: löschen)──▶ entfernt
```
Abgeleitet: `ausgegeben` und Ende < heute ⇒ **Überfällig**.
Nach `zurueck`: Admin erfasst eingesammelte Münzen (`coins`), solange `null` erscheint der Eintrag unter „Münzen zählen“.

Tag-Farben: reserviert = `.tag-outline` („Schlüssel abzuholen“), ausgegeben = `.tag-accent`, gemeldet/überfällig = `.tag-accent-2`, zurück/abgesagt = `.tag-neutral`.

---

## Datenmodell (Vorschlag)
```
Stiege        { nummer 1–15, anzahlWohnungen, hatSauna: bool }
Wohnung       { stiege, top (1..n) }                       // implizit aus anzahlWohnungen
Partei        { id, vorname, nachname, stiege, top, benutzername (unique), passwortHash, rolle: 'user'|'admin', aktiv }
Reservierung  { id, parteiId, saunaStiege, datum (bei Wochenende: Samstag), wochenende: bool,
                status: reserviert|ausgegeben|gemeldet|zurueck|abgesagt, muenzenEingesammelt: int|null, notiz }
Muenzverkauf  { id, parteiId, datum, anzahl, preisProStueck }           // Admin-Erfassung
Einstellungen { preisProMuenze, minutenProMuenze, muenzenGesamt, moDoVon, moDoBis, frVon, frBis }
// User-eigene Daten
MuenzKaufUser { id, parteiId, datum, anzahl, preisProStueck }           // eigene Aufzeichnung der Partei
Tagebuch      { reservierungId, muenzen, temperatur °C, saunagaenge, aufguss: bool,
                oele: [{ oelId|name, tropfen }], dosierung: 'zu schwach'|'passend'|'zu stark', oelNotiz, notiz }
Oel           { id, parteiId, name, marke?, notiz? }                    // persönliche Öl-Liste, Seed: 9 Standardöle
```
Benutzername-Generierung: `vorname.nachname` klein, Umlaute → ae/oe/ue/ss, bei Kollision Zahl anhängen. Startpasswort: zufällig (Prototyp: `sauna-1234`), Änderung beim ersten Login.

---

## Screens

### Gemeinsamer Rahmen (nach Login)
- Container `max-width: 1280px`, Padding `0 40px`, Inhalt `padding: 56px 40px 140px`.
- **Kopf**: `.nav` – links `Saunabuch` (24px, 600) + Rollenlabel (13px, 62 % Ink), rechts Navigationslinks (14px, aktiver Link `aria-current="page"` → Akzentfarbe) + „Abmelden“ (`.btn-ghost`).
- **Datumsleiste** (Zeitungs-„Dateline“): Linie 3px Ink, 2px Abstand, 1px Ink; Zeile 13px mit Datum · Mitte (Admin: „Schlüsselausgabe heute 08:00–18:00“, User: Name · Stiege/Top) · rechts „5 Saunen · Stiege 2, 5, 8, 11, 14“; darunter 1px Ink.
- **Kicker** über Überschriften: 12px, uppercase, letter-spacing 0.08em, 70 % Ink.
- **Listen** statt Tabellen: CSS-Grid-Zeilen, `padding: 14px 0`, Trennung `1px solid color-mix(in srgb, var(--color-text) 10%, transparent)`; Kopfzeile 11px uppercase, 60 % Ink, `border-bottom: 1px solid var(--color-divider)`.
- **Toast** unten links: Surface-Hintergrund, `--shadow-lg`, 14px, 4,2 s.
- **Dialoge**: `.dialog-backdrop` + `.dialog` (max 500px), Klick auf Backdrop schließt.

### 1. Login
Zweispaltig (flex-wrap). Links: Dateline („Wohnanlage · Stiegen 1–15“ | Datum), Titel „Saunabuch“ (clamp 60–96px, 600, letter-spacing −0.035em), Untertitel 23px „Reservierungen, Schlüssel und Münzen für die Saunen im Dachgeschoß.“, drei Info-Spalten (`h6`): Schlüsselausgabe / Wochenende / Münzen. Rechts (max 360px): „Anmelden“, Felder Benutzername + Passwort (`.field/.input`), Fehler in `--color-accent-2-700`, Button `.btn-primary`, Hinweis „Zugänge vergibt der Hauswart. Eine Selbstregistrierung ist nicht vorgesehen.“
Validierung: leere Felder → „Bitte Benutzername und Passwort eingeben.“; unbekannt → „Benutzername oder Passwort stimmt nicht.“

### Admin
**2. Heute** – Hauptspalte (flex 1 1 600px) + Seitenleiste (290px).
- Headline 50px, dynamisch: „Sieben Schlüssel warten auf Abholung. Im Postkasten sind zwei zu prüfen.“ (Zahlwörter bis 12).
- **Postkasten prüfen**: Reservierungen mit Status `gemeldet` oder überfällig. Spalten: Termin · Sauna · Partei (+ Stiege/Top) · Status-Tag · Button „Schlüssel ist da“ (primary → `zurueck`).
- **Schlüssel abholen lassen**: `reserviert`, Datum heute bis heute+3. Spalte Abholfrist („bis Fr 2. Okt, 12:00“) · „Ausgegeben“ (secondary).
- **Münzen zählen**: `zurueck` mit `coins == null`, Zahl-Input + „Erfassen“.
- Seitenleiste: „Münzen im Lager“ als großes Plattenziffer-Numeral (`.cmyk-num`, 128px), „von 400 Münzen · N bei Parteien“, Kennzahlen Verkauft / Eingesammelt / Einnahmen 2026; **Schlüsselbrett** je Sauna: „Beim Hauswart“ / „Bei <Name> seit …“ / „Im Postkasten (gemeldet)“ mit Tag.

**3. Kalender** – Wochenansicht, KW + Zeitraum als H1, Buttons „← Vorwoche / Diese Woche / Nächste Woche →“.
Grid: `110px | 5 × Mo–Fr | 1.3fr Wochenende`, gap 10px. Zeile je Sauna. Belegte Zelle = `.card` als Button (min-height 104px, Name 14px/600, Stiege/Top 12px, Status-Tag; Hover `--shadow-md`) → Detaildialog mit Statusaktionen, Münzerfassung, „Stornieren“. Freie Zukunftszelle = `.btn-secondary` gestrichelt „frei · anlegen“ → Dialog „Neue Reservierung“ mit Partei-Auswahl. Vergangen & frei = „—“. Heute-Spalte im Kopf in `--color-accent-700`, 600. Legende des Status-Ablaufs unten.

**4. Stiegen & Parteien** – Grid `260px | 1fr`.
Links: „15 Stiegen“, Summenzeile („186 Wohnungen · N Parteien · 5 Saunen“), Liste der Stiegen (aktiv = Surface-Hintergrund, Hover `--color-neutral-200`, Tag „Sauna“ wenn vorhanden).
Rechts: H1 „Stiege n“, Button „Partei registrieren“; Felder Anzahl Wohnungen (1–40), Sauna im Dachgeschoß (`.seg` Ja/Nein), Anzahl Parteien. Liste der Wohnungen „Top 1…n“, je Partei Name · Benutzername · „Entfernen“ (Bestätigungsdialog), „+ Partei“ je Wohnung.
Dialog „Partei registrieren“: Vorname, Nachname, Wohnung (Select), Startpasswort (readonly, generiert), Vorschau Benutzername, „Zugang anlegen“ (disabled bis Vor- & Nachname). Toast zeigt Zugangsdaten.

**5. Münzen & Abrechnung**
H1 „N Münzen im Lager, M bei den Parteien.“; 4 Kennzahlen (40px/600): gesamt, verkauft, eingesammelt, Einnahmen.
Links: **Verkauf erfassen** (Partei-Select „Stiege x / Top y – Nachname Vorname“, Anzahl, Datum, Live-Summe „10 × 5,00 € = 50,00 €“; Validierung: Partei, Anzahl > 0, ≤ Lager) + **Letzte Verkäufe** (10).
Rechts: **Übrige Münzen je Partei** (gekauft / verwendet / übrig, sortiert absteigend) + **Grundwerte** (Preis/Münze, Minuten/Münze, Münzen gesamt, Ausgabezeiten Mo–Do und Fr als `type=time`). Änderungen wirken sofort überall (Texte, Fristen).

### User
**6. Saunagänge** (Start) – Hauptspalte + Seitenleiste.
- Falls Schlüssel offen (ausgegeben & vorbei): magenta Kicker „Schlüssel offen“, Text, Button „Schlüssel liegt im Postkasten“ → Status `gemeldet`.
- **Nächster Saunagang**: H1 54px („Samstag, 3. – Sonntag, 4. Oktober“), „Sauna Stiege 5 · Dachgeschoß“, 3 Spalten Schlüssel abholen / Schlüssel zurück / Status; „Bearbeiten“ + „Absagen“ nur bei `reserviert`.
- **Weitere Termine**, **Vergangene Saunagänge** (Termin, Sauna, Temp., Aufguss/Öle, Münzen, Kosten, Link „Tagebuch“ / „Eintrag schreiben“), **Abgesagt** (durchgestrichen, „Löschen“).
- Seitenleiste: „Münzen übrig“ (`.cmyk-num`), „x gekauft · y verbraucht“, Warnung bei < 3, Ausgabezeiten + Wochenendregel, „Neue Reservierung“.

**7. Reservieren / Bearbeiten** – 3 Spalten (flex-wrap): 1 · Sauna (Radio-Liste, je „frei ab <nächster freier Tag>“), 2 · Tag (Monatskalender Okt–Dez, 7 Spalten, Zellen = `.btn` 66px hoch: gewählt `.btn-primary`, frei `.btn-secondary`, belegt / eigener Termin / vergangen disabled; Unterzeile „frei/belegt/Ihr Termin/heute/Wochenende“; Klick auf Sa oder So wählt den Wochenendblock), 3 · Übersicht (Termin, Abholfrist, Rückgabe, Münzhinweis, Notiz an Hauswart, CTA „Verbindlich reservieren“ bzw. „Änderung speichern“). Beim Wechsel der Sauna bleibt das Datum nur, wenn es dort frei ist.

**8. Saunatagebuch** – Grid `260px | 1fr`. Links Liste vergangener Saunagänge (ohne Eintrag: Unterzeile magenta „Noch kein Eintrag“). Rechts Editor:
- Temperatur (56px Anzeige + Range 50–110 °C), Münzen (Stepper, zeigt Kosten + Heizminuten), Saunagänge (Stepper).
- Aufguss `.seg` Ja/Nein. Bei Ja: Öl-Chips (Mehrfachauswahl, aktiv `.btn-primary`), Feld „Anderes Öl“ + „Hinzufügen“ (legt in „Meine Öle“ an und wählt aus), Link „Meine Öle“; je gewähltem Öl Tropfen-Stepper (Start 3, min 1), Summe; Dosierung `.seg` zu schwach/passend/zu stark; Notiz zur Dosierung.
- Notiz zum Saunagang, „Eintrag speichern“.

**9. Öle** – Liste der persönlichen Öle: Name + Verwendung („4× · 13 Tropfen“, aus Tagebuch aggregiert), Marke & Notiz inline editierbar, „Entfernen“ (Bestätigung; bestehende Einträge bleiben). Rechts Formular „Öl hinzufügen“ (Name Pflicht, Duplikate abgelehnt).

**10. Münzen** – H1 „N Münzen übrig.“; Kennzahlen gekauft / verbraucht laut Tagebuch / ausgegeben / Ø Preis pro Münze. Liste der Käufe (löschbar) + Formular „Kauf erfassen“ (Datum, Anzahl, Preis/Stk., Live-Summe).

**11. Jahresbilanz** – Anzahl Saunagänge als großes `.cmyk-num` (220px) + H1 „Saunagänge, X € für Münzen.“ + Zusammenfassung (Ø Kosten/Gang, Wochenenden, meistverwendetes Öl). Kennzahlen: Münzen, Stunden Heizzeit, Ø/Max Temperatur, Aufgüsse, Lieblingssauna. Balkendiagramm nach Monat (Höhe ∝ Anzahl, max 150px, `--color-accent`, darunter Monat + Kosten). „Öle im Aufguss“: Liste mit Tropfen/Anzahl und Balken (`--color-accent-2`), Zeile „Bei x von y Aufgüssen passte die Dosierung.“ Kosten = verbrauchte Münzen × Ø Kaufpreis.

---

## Design Tokens (Broadsheet, aus `styles.css`)
- Hintergrund `--color-bg #f3f2f2`, Surface `--color-surface #eae9e9`, Text `--color-text #201e1d`, Divider = Text 16 %
- Akzent (Cyan) `--color-accent #0088b0` (Hover 600 `#1186ac`, Pressed 700 `#006786`; kleiner Text in 700)
- Akzent 2 (Magenta) `--color-accent-2 #d6006c` (Text 700 `#aa0b56`) – sparsam, für Warnungen/Aufmerksamkeit
- Prozessgelb `#edbb00` nur in der Plattenziffer
- Ramps 100–900 für neutral/accent/accent-2 siehe `styles.css`
- Schrift: **Source Serif 4** (400, 600, Italic 400) für alles. Basis 15px/1.55. H1 42 · H2 32 · H3 25 · H4 20 · H6 13 uppercase 0.08em. Display-Headlines 46–96px, letter-spacing −0.025 bis −0.035em
- Abstände: 5 / 10 / 15 / 20 / 30 / 40 px (Layout nutzt großzügig 48–88px zwischen Bereichen)
- Radius: 1 / 2 / 4 px
- Schatten: sm `0 1px 2px` · md `0 3px 10px` · lg `0 12px 32px` (Ink #2d2b2b 14–22 %)
- Fokus: `outline: 2px solid var(--color-accent); outline-offset: 2px`, disabled = 45 % Opazität
- Komponenten-Klassen: `.btn(-primary/-secondary/-ghost/-icon/-block)`, `.tag(-accent/-accent-2/-neutral/-outline)`, `.field`, `.input`, `.radio .dot`, `.seg .seg-opt`, `.card`, `.nav .nav-brand`, `.dialog-*`, `.cmyk-num`

## Assets
Keine Bilder/Icons. Schrift via Google Fonts (Source Serif 4). Plattenziffer `.cmyk-num` ist reines CSS (`styles.css`).

## Offene Punkte für die Umsetzung
- Wochenende als Block (Sa+So) bestätigen.
- Passwort-Reset durch Admin, Erst-Login-Passwortwechsel.
- Optional: E-Mail/SMS-Erinnerung an Abholfrist und Rückgabe.
- Sauna deaktivieren, wenn noch Reservierungen bestehen → Warnung.
- Mehrjährigkeit (Jahresauswahl in Bilanz/Abrechnung).

## Dateien
- `prototype/Saunabuch.dc.html` – kompletter Prototyp (Template + Logik inkl. Seed-Daten und Regeln)
- `prototype/support.js` – Runtime für den Prototyp (nicht übernehmen)
- `prototype/_ds/.../styles.css` – Design-System-Tokens und Komponenten-CSS
- `prototype/_ds/.../_ds_bundle.js` – Design-System-Bundle (für den Prototyp)
