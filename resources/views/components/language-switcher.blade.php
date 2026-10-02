{{--
    Bascule de langue.
    - variant "adminlte" (defaut) : rendu historique, inchange, pour les pages AdminLTE.
    - variant "tailwind" : rendu Tailwind pour les pages migrees.
    La liste des locales reste ainsi definie a un seul endroit.
--}}
@props(['variant' => 'adminlte'])

@php
    $locales = ['fr' => 'Français', 'en' => 'English'];
@endphp

@if ($variant === 'tailwind')
    <div class="qpos-language-switcher flex items-center gap-1 rounded-lg border border-qpos-line bg-qpos-surface p-0.5" role="group"
        aria-label="{{ __('Language') }}">
        @foreach ($locales as $locale => $label)
            <form action="{{ route('language.update') }}" method="post">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button type="submit"
                    class="rounded-md px-2 py-1 text-xs font-semibold transition {{ app()->getLocale() === $locale ? 'bg-qpos-brand text-white' : 'text-qpos-brand-ink hover:bg-qpos-page' }}"
                    aria-label="{{ $label }}"
                    aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                    title="{{ $label }}">{{ strtoupper($locale) }}</button>
            </form>
        @endforeach
    </div>
@else
    <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Language') }}">
        @foreach ($locales as $locale => $label)
            <form class="d-inline" action="{{ route('language.update') }}" method="post">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button
                    class="btn {{ app()->getLocale() === $locale ? 'btn-primary' : 'btn-outline-secondary' }}"
                    type="submit"
                    aria-label="{{ $label }}"
                    aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                    title="{{ $label }}"
                >{{ strtoupper($locale) }}</button>
            </form>
        @endforeach
    </div>
@endif
