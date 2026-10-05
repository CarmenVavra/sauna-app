{{-- Meldung unten links (Session-„toast“ oder erster Validierungsfehler). --}}
<div class="toast" role="status" aria-live="polite"
     x-data="toast(@js(session('toast') ?? ($errors->any() ? $errors->first() : '')))"
     x-show="visible" x-cloak x-text="message"></div>
