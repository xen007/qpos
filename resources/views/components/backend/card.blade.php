{{--
    Carte de contenu (skin Tailwind).
    Slots optionnels : actions (en-tete, a droite), footer.
--}}
@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-qpos-line bg-qpos-surface shadow-sm']) }}>
    @if ($title)
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-qpos-line px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-qpos-ink">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-qpos-muted">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padded ? 'px-6 py-5' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-qpos-line px-6 py-4">{{ $footer }}</footer>
    @endisset
</section>
