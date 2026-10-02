{{--
    Carte d'indicateur (skin Tailwind).
    $value est affiche tel quel : le formatage (devise, nombres) reste a la
    charge de la page, qui maitrise la locale.
--}}
@props(['label', 'value', 'icon', 'href' => null, 'hint' => null])

<div class="qpos-card qpos-stat-card flex h-full flex-col justify-between p-5">
    <div class="flex items-start gap-4">
        <span class="qpos-stat-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-xl">
            <x-backend.icon :name="$icon" />
        </span>

        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-qpos-muted">{{ $label }}</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-qpos-ink">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-qpos-muted">{{ $hint }}</p>
            @endif
        </div>
    </div>

    @if ($href)
        <a href="{{ $href }}"
            class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-qpos-brand-ink transition hover:opacity-80">
            {{ __('More info') }}
            <x-backend.icon name="arrow-right" />
        </a>
    @endif
</div>
