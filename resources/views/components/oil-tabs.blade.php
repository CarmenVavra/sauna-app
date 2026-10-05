@props(['current'])
{{-- Unternavigation im Bereich „Öle“. --}}
<nav class="subnav" aria-label="Öle">
    <a href="{{ route('user.oils.index') }}" @if ($current === 'oils') aria-current="page" @endif>Meine Öle</a>
    <a href="{{ route('user.blends.index') }}" @if ($current === 'blends') aria-current="page" @endif>Mischungen</a>
</nav>
