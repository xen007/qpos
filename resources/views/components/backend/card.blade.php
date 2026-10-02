{{--
    Carte de contenu (skin Tailwind).
    Slots optionnels : actions (en-tete, a droite), footer.
--}}
@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'qpos-card']) }}>
    @if ($title)
        <header class="qpos-card-header flex flex-wrap items-center justify-between gap-3 px-6 py-4">
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
        <footer class="qpos-card-footer px-6 py-4">{{ $footer }}</footer>
    @endisset
</section>
