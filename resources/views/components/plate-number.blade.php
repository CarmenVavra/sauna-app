@props(['value', 'size' => null])
{{-- Plattenziffer: drei versetzte Druckplatten (C, M, Y) über dem Papier. --}}
<div {{ $attributes->class(['cmyk-num', $size]) }}>
    <span class="paper">{{ $value }}</span>
    <span class="plate plate-c" aria-hidden="true">{{ $value }}</span>
    <span class="plate plate-m" aria-hidden="true">{{ $value }}</span>
    <span class="plate plate-y" aria-hidden="true">{{ $value }}</span>
</div>
