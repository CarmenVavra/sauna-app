<x-app-layout title="Passwort ändern">
    <div style="max-width:420px">
        <x-kicker>{{ $firstLogin ? 'Erster Login' : 'Zugang' }}</x-kicker>
        <h1>Neues Passwort setzen</h1>
        @if ($firstLogin)
            <p class="text-14 muted">Bitte ersetzen Sie das Startpasswort vom Hauswart durch ein eigenes Passwort (mindestens 8 Zeichen).</p>
        @endif

        <form method="POST" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:16px;margin-top:24px" novalidate>
            @csrf
            @method('PUT')
            <div class="field">
                <label for="current_password">{{ $firstLogin ? 'Startpasswort' : 'Aktuelles Passwort' }}</label>
                <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required
                       @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror>
                @error('current_password')<p id="current_password-error" class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password">Neues Passwort</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" required minlength="8"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')<p id="password-error" class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Neues Passwort wiederholen</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-start">Passwort speichern</button>
        </form>
    </div>
</x-app-layout>
