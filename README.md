# Profi-Tipp für Laravel-Entwickler: "Laravel Boost MCP"

Um Claude Code noch mächtiger im Umgang mit Ihrer Datenbank zu machen, empfiehlt sich die Installation des Open-Source-Pakets `laravel/boost` via Composer im Projekt:

```bash
composer require laravel/boost --dev
```

## Dieses Paket registriert sich als sogenannter Model Context Protocol (MCP) Server. Dadurch kann Claude Code direkt mit den Internals Ihrer Laravel-App interagieren. Claude kann dann

- Live Ihre echte lokale Datenbank-Struktur abfragen (ohne die Dateien lesen zu müssen).
- Artisan Tinker-Befehle im Hintergrund ausführen, um Daten direkt zu prüfen.

## Server start and stop script

- Mach im Projektordner eine start.bat, um den Server zu starten
- Mach im Projektordner eine stop.bat, um den Server zu soppen

---

# Saunabuch – Umsetzung

Laravel-12-App (PHP 8.2, SQLite, Blade + Alpine.js, Vite) nach den klickbaren Mockups in `UI_MOCKUPS/` (Design-System „Broadsheet“).

## Starten & Stoppen

| Datei | Wirkung |
| --- | --- |
| `start.bat` | installiert bei Bedarf Abhängigkeiten, legt `.env` und die SQLite-Datenbank (mit Demo-Daten) an, baut das Frontend und startet den Server auf **http://127.0.0.1:8200** |
| `stop.bat` | beendet den Server auf Port 8200 |

Port 8200 statt 8000, weil 8000 auf diesem Rechner bereits von einem anderen PHP-Prozess belegt ist.

## Demo-Zugänge

| Rolle | Benutzername | Startpasswort |
| --- | --- | --- |
| Admin | `admin.gregor` | `password` |
| Hauswart (Admin) | `hauswart` | `hauswart-2026` |
| Partei | `maria.huber` | `sauna-1234` |
| alle anderen Demo-Parteien | `vorname.nachname` | `sauna-1234` |

**Alle Zugänge müssen beim ersten Login ein eigenes Passwort setzen** (mind. 8 Zeichen, anders als das Startpasswort). Das gilt auch für Parteien, die der Hauswart neu registriert oder deren Passwort er zurücksetzt.

`admin.gregor` wird von `database/seeders/AdminSeeder.php` angelegt; einzeln (ohne die übrigen Daten anzutasten) mit `php artisan db:seed --class=AdminSeeder`.

Mit `SAUNA_DEMO_LOGINS=true` in der `.env` erscheinen auf der Login-Seite die Schnellzugänge „Als Hauswart“ / „Als Maria Huber“ wie im Prototyp. **In Produktion auf `false` lassen.**

Die Demo-Daten (`database/seeders/DemoSeeder.php`) sind eine Portierung der Seed-Funktion des Prototyps. Der Prototyp spielt in der Woche ab Mo 28.09.2026; der Seeder verschiebt alle Daten um ganze Wochen in die aktuelle Woche. Neu aufsetzen: `php artisan migrate:fresh --seed`.

## Befehle

```bash
php artisan test
./vendor/bin/phpstan analyse
./vendor/bin/pint
npm run build
```

## Aufbau

- **Models** (`app/Models`): `Staircase` (Stiege, Schlüssel = Nummer), `User` (Hauswart oder Partei, Soft Delete), `Reservation` (Statusübergänge `moveTo()`, `recordCoins()`), `CoinSale` (Verkauf durch Hauswart), `CoinPurchase` (eigene Aufzeichnung der Partei), `Oil`, `DiaryEntry`, `DiaryEntryOil`, `Setting` (Grundwerte, eine Zeile).
- **Regeln** (`app/Services/BookingService.php`, `app/Support/Block.php`): ganzer Tag, Wochenende als Block (Samstag als Datum), Wochenende nur bis Freitag reservierbar, eine Partei pro Sauna und Tag, keine zwei Reservierungen einer Partei am selben Tag.
- **Schlüssel-Ablauf** (`app/Enums/ReservationStatus.php`): reserviert → ausgegeben → gemeldet → zurück, gemeldet → ausgegeben („nicht im Postkasten“), reserviert → abgesagt; ausgegeben + vorbei ⇒ „Überfällig“.
- **Münzen** (`app/Services/CoinLedger.php`, `PersonalCoins.php`): bei Parteien = verkauft − eingesammelt, im Lager = Gesamtbestand − bei Parteien.
- **Controller** schlank, Validierung in Form Requests, Berechtigungen über Policies und Rollen-Middleware.
- **Views** in `resources/views` (Blade-Komponenten in `components/`), Styles in `resources/css/app.css`, Interaktion (Kalender, Tagebuch, Dialoge, Toast) in `resources/js/app.js`.

## Entscheidungen & Abweichungen vom Prototyp

- Kleinste Schriftgröße 12 px (Prototyp teils 11 px) laut Projektvorgabe.
- Grundwerte und Stiegen-Daten werden mit „Speichern“ übernommen (Prototyp: sofort beim Tippen).
- Erst-Login: Parteien mit Startpasswort müssen zuerst ein eigenes Passwort setzen. Der Hauswart kann ein neues Startpasswort vergeben.
- Sauna deaktivieren mit offenen künftigen Reservierungen: Warnung, nach Bestätigung werden diese storniert.
- Entfernte Parteien werden nur deaktiviert (Soft Delete) – ihre Saunagänge und Münzen bleiben in der Abrechnung.
- Kennzahlen „verkauft / eingesammelt / Einnahmen“ beziehen sich auf das laufende Jahr, der Lagerbestand auf alle Jahre.

## Offene Punkte (aus dem Handoff)

- Wochenende als Block (Sa+So) mit Auftraggeber bestätigen.
- Optional: E-Mail/SMS-Erinnerung an Abholfrist und Rückgabe.
- Mehrjährigkeit (Jahresauswahl in Bilanz/Abrechnung).
- Laravel Boost ist installiert (`composer require laravel/boost --dev`); `php artisan boost:install` einmal interaktiv ausführen, um den MCP-Server einzurichten (ergänzt dabei Richtlinien in `CLAUDE.md`).
