{{--
    Menu deroulant (skin Tailwind).
    Comportement gere par resources/js/shell.js (clic exterieur, Escape).
    Slot $trigger : bouton d'ouverture ; slot par defaut : contenu du panneau.
--}}
@props(['align' => 'right'])

<div class="relative" data-qpos-dropdown>
    <button type="button" data-qpos-dropdown-toggle aria-haspopup="true" aria-expanded="false"
        {{ $trigger->attributes->merge(['class' => 'qpos-dropdown-trigger flex items-center gap-2 px-3 py-2 text-sm font-medium']) }}>
        {{ $trigger }}
    </button>

    <div data-qpos-dropdown-panel
        class="qpos-dropdown-panel absolute z-30 mt-2 hidden w-56 overflow-hidden rounded-xl border border-qpos-line bg-qpos-surface py-1 shadow-lg {{ $align === 'right' ? 'right-0' : 'left-0' }}">
        {{ $slot }}
    </div>
</div>
