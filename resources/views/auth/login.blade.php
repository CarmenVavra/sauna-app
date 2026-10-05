<x-guest-layout title="Anmelden">
    <div class="login">
        <div class="login-intro">
            <div class="rule-double"></div>
            <div class="dateline">
                <span>Wohnanlage · Stiegen 1–15</span>
                <span>{{ $layout['today'] }}</span>
            </div>
            <div class="rule"></div>
            <h1 class="login-title">Saunabuch</h1>
            <p class="login-sub">Reservierungen, Schlüssel und Münzen für die Saunen im Dachgeschoß.</p>
            <div class="login-facts">
                <div>
                    <h2 class="h6">Schlüsselausgabe</h2>
                    <p class="text-14 mb-0">{{ $layout['hours']['monThu'] }}<br>{{ $layout['hours']['fri'] }}</p>
                </div>
                <div>
                    <h2 class="h6">Wochenende</h2>
                    <p class="text-14 mb-0">Abholung bis Freitag {{ $layout['hours']['friUntil'] }}, Rückgabe Sonntagabend in den Postkasten.</p>
                </div>
                <div>
                    <h2 class="h6">Münzen</h2>
                    <p class="text-14 mb-0">{{ $layout['hours']['coin'] }}</p>
                </div>
            </div>
        </div>

        <div class="login-form">
            <form method="POST" action="{{ route('login.store') }}" class="login-form" style="padding-top:0;min-width:0" novalidate>
                @csrf
                <h2 class="h3 mb-0">Anmelden</h2>
                <div class="field">
                    <label for="username">Benutzername</label>
                    <input id="username" name="username" class="input" value="{{ old('username') }}" placeholder="vorname.nachname"
                           autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus
                           @error('username') aria-invalid="true" aria-describedby="login-error" @enderror>
                </div>
                <div class="field">
                    <label for="password">Passwort</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="current-password" required
                           @error('username') aria-invalid="true" aria-describedby="login-error" @enderror>
                </div>
                @if ($errors->any())
                    <p id="login-error" class="error mt-0" role="alert">{{ $errors->first() }}</p>
                @endif
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;padding-inline:28px">Anmelden</button>
                <p class="text-13 muted" style="margin:8px 0 0">Zugänge vergibt der Hauswart. Eine Selbstregistrierung ist nicht vorgesehen.</p>
            </form>

            @if ($demoLogins)
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:28px">
                    <span class="kicker mb-0">Demo-Zugänge</span>
                    <div class="btn-row">
                        <form method="POST" action="{{ route('login.demo', 'admin') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary">Als Hauswart</button>
                        </form>
                        <form method="POST" action="{{ route('login.demo', 'user') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary">Als Maria Huber</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-guest-layout>
