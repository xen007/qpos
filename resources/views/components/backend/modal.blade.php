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
    class="w-[calc(100%-2rem)] max-w-lg rounded-xl border border-qpos-line bg-qpos-surface p-0 text-qpos-ink shadow-xl backdrop:bg-black/50">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if (! in_array(strtoupper($method), ['GET', 'POST']))
            @method($method)
        @endif

        <header class="flex items-center justify-between gap-4 border-b border-qpos-line px-6 py-4">
            <h2 id="{{ $id }}-title" class="text-base font-semibold text-qpos-ink">{{ $title }}</h2>
            <button type="button" data-qpos-modal-close aria-label="{{ __('Close') }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div class="space-y-4 px-6 py-5">
            {{ $slot }}
        </div>

        <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-qpos-line px-6 py-4">
            <button type="button" data-qpos-modal-close
                class="rounded-lg border border-qpos-line px-4 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page">
                {{ __('Close') }}
            </button>
            <button type="submit"
                class="rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                {{ $submitLabel ?? __('Submit') }}
            </button>
        </footer>
    </form>
</dialog>
