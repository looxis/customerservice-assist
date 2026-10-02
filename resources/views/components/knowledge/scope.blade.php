@props(['label', 'values'])

{{-- One scope or tag list of a knowledge document; an empty list means "applies to all". --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1']) }}>
    <span class="text-slate-400">{{ $label }}:</span>
    <span class="font-mono text-slate-700">{{ $values === [] ? 'alle' : implode(', ', $values) }}</span>
</span>
