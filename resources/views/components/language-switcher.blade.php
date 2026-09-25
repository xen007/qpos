<div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Language') }}">
    @foreach (['fr' => 'Français', 'en' => 'English'] as $locale => $label)
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
