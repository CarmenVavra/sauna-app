@props(['value', 'label', 'small' => false])
<div {{ $attributes }}>
    <div @class(['stat-value', 'stat-value-sm' => $small])>{{ $value }}</div>
    <div class="stat-label">{{ $label }}</div>
</div>
