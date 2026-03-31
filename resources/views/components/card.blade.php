@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-6',
])

<section {{ $attributes->merge(['class' => "fin-card {$padding}"]) }}>
    @if ($title || $subtitle)
        <header class="mb-5 flex items-start justify-between gap-4">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>

            @if (isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
