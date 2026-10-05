@props([
    'id',
    'title',
    'body',
    'action',
    'method' => 'DELETE',
    'confirm' => 'Löschen',
    'trigger',
    'triggerClass' => 'btn btn-ghost btn-small',
])
{{-- Schaltfläche mit Bestätigungsdialog für unumkehrbare Aktionen. --}}
<button type="button" class="{{ $triggerClass }}" data-dialog-open="{{ $id }}" aria-haspopup="dialog" {{ $attributes }}>{{ $trigger }}</button>
<dialog id="{{ $id }}" class="dialog" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-body">
    <h2 class="dialog-title" id="{{ $id }}-title">{{ $title }}</h2>
    <p class="dialog-body" id="{{ $id }}-body">{{ $body }}</p>
    <form method="POST" action="{{ $action }}" class="dialog-actions">
        @csrf
        @method($method)
        {{ $slot }}
        <button type="button" class="btn btn-secondary" data-dialog-close>Abbrechen</button>
        <button type="submit" class="btn btn-primary">{{ $confirm }}</button>
    </form>
</dialog>
