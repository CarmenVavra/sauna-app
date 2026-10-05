@props(['credentials'])
{{-- Zeigt neue Zugangsdaten (Registrierung / Passwort zurückgesetzt), bis der Hauswart den Dialog schließt. --}}
@php($text = 'Benutzername: '.$credentials['username']."\nStartpasswort: ".$credentials['password'])
<dialog id="credentials-dialog" class="dialog" aria-labelledby="credentials-title" aria-describedby="credentials-hint"
        x-data="copyText(@js($text))" x-init="$el.showModal()">
    <p class="kicker mb-0">{{ $credentials['name'] }}</p>
    <h2 class="dialog-title" id="credentials-title">{{ $credentials['heading'] }}</h2>
    <dl class="dialog-grid credentials">
        <dt>Benutzername</dt>
        <dd><code>{{ $credentials['username'] }}</code></dd>
        <dt>Startpasswort</dt>
        <dd><code>{{ $credentials['password'] }}</code></dd>
    </dl>
    <p class="dialog-body" id="credentials-hint">Bitte jetzt notieren oder kopieren und der Partei persönlich übergeben – das Passwort wird danach nicht mehr angezeigt. Beim ersten Login muss die Partei ein eigenes Passwort setzen.</p>
    <p class="sr-only" aria-live="polite" x-text="copied ? 'Zugangsdaten kopiert' : ''"></p>
    <div class="dialog-actions">
        <button type="button" class="btn btn-secondary" @click="copy()" x-text="copied ? 'Kopiert ✓' : 'Kopieren'">Kopieren</button>
        <button type="button" class="btn btn-primary" data-dialog-close autofocus>Fertig</button>
    </div>
</dialog>
