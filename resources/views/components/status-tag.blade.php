@props(['reservation', 'today'])
<span {{ $attributes->class(['tag', $reservation->statusClass($today)]) }}>{{ $reservation->statusLabel($today) }}</span>
