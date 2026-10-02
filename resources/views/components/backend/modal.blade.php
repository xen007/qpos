{{--
    Modale de formulaire (skin Tailwind).

    Repose sur l'element natif <dialog> : ouverture, fermeture par Echap et
    piege de focus sont geres par le navigateur, sans Bootstrap ni bibliotheque.
    Le comportement (ouverture, fermeture, clic sur le fond) est assure par
    resources/js/shell.js.

    Usage :
        <x-backend.modal id="roleModal" :title="__('Add new role')"
            :action="route('backend.admin.roles.create')" :submit-label="__('Submit')">
            ... champs ...
        </x-backend.modal>
--}}
@props(['id', 'title', 'action', 'method' => 'POST', 'submitLabel' => null])

<dialog id="{{ $id }}" data-qpos-modal aria-labelledby="{{ $id }}-title"
    class="qpos-modal w-[calc(100%-2rem)] max-w-lg rounded-xl border border-qpos-line bg-qpos-surface p-0 text-qpos-ink shadow-xl backdrop:bg-black/50">
    <form action="{{ $action }}" method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}">
        @if (strtoupper($method) !== 'GET') @csrf @endif
        @if (! in_array(strtoupper($method), ['GET', 'POST']))
            @method($method)
        @endif

        <header class="qpos-card-header flex items-center justify-between gap-4 px-6 py-4">
            <h2 id="{{ $id }}-title" class="text-base font-semibold text-qpos-ink">{{ $title }}</h2>
            <button type="button" data-qpos-modal-close aria-label="{{ __('Close') }}"
                class="qpos-icon-button flex h-9 w-9 items-center justify-center">
                <x-backend.icon name="x" />
            </button>
        </header>

        <div class="space-y-4 px-6 py-5">
            {{ $slot }}
        </div>

        <footer class="qpos-card-footer flex flex-wrap items-center justify-end gap-2 px-6 py-4">
            <x-backend.button type="button" variant="secondary" data-qpos-modal-close>{{ __('Close') }}</x-backend.button>
            <x-backend.button>{{ $submitLabel ?? __('Submit') }}</x-backend.button>
        </footer>
    </form>
</dialog>
