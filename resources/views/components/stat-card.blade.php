@props([
    'label',
    'amount',
    'tone' => 'neutral',
    'bars' => [],
])

@php
    $toneClasses = match ($tone) {
        'income' => 'bg-emerald-50 text-emerald-700',
        'expense' => 'bg-rose-50 text-rose-700',
        default => 'bg-stone-100 text-stone-700',
    };

    $barClasses = match ($tone) {
        'income' => 'bg-emerald-400/80',
        'expense' => 'bg-amber-400/80',
        default => 'bg-slate-400/70',
    };
@endphp

<div {{ $attributes->merge(['class' => 'fin-stat-card']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <span class="fin-stat-label">{{ $label }}</span>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $amount }}</div>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ $toneClasses }}">
            {{ $tone }}
        </span>
    </div>

    <div class="mt-6 flex h-14 items-end gap-1.5">
        @foreach ($bars as $bar)
            <span class="mini-bar {{ $barClasses }}" style="height: {{ max(12, min(100, $bar)) }}%;"></span>
        @endforeach
    </div>
</div>
