{{--
    Carte d'indicateur (skin Tailwind).
    $value est affiche tel quel : le formatage (devise, nombres) reste a la
    charge de la page, qui maitrise la locale.
--}}
@props(['label', 'value', 'icon', 'href' => null, 'hint' => null])

<div class="flex h-full flex-col justify-between rounded-xl border border-qpos-line bg-qpos-surface p-5 shadow-sm">
    <div class="flex items-start gap-4">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-qpos-page text-qpos-brand">
            <i class="{{ $icon }} text-lg" aria-hidden="true"></i>
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
            class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-qpos-brand transition hover:opacity-80">
            {{ __('More info') }}
            <i class="fas fa-arrow-circle-right" aria-hidden="true"></i>
        </a>
    @endif
</div>
