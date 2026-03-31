@props([
    'name',
    'description',
    'amount',
    'direction' => 'incoming',
    'time' => null,
])

@php
    $incoming = $direction === 'incoming';
    $avatarPalette = $incoming
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-rose-100 text-rose-700';
@endphp

<div {{ $attributes->merge(['class' => 'transaction-row']) }}>
    <div class="flex items-center gap-3">
        <div class="transaction-avatar {{ $avatarPalette }}">
            {{ strtoupper(substr($name, 0, 1)) }}
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-900">{{ $name }}</div>
            <div class="text-xs text-slate-500">{{ $description }}</div>
        </div>
    </div>

    <div class="text-right">
        <div class="text-sm font-semibold {{ $incoming ? 'text-emerald-600' : 'text-rose-500' }}">
            {{ $incoming ? '+' : '-' }}{{ $amount }}
        </div>
        @if ($time)
            <div class="text-xs text-slate-400">{{ $time }}</div>
        @endif
    </div>
</div>
