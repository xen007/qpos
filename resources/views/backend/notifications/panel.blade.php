@if(app(\App\Services\OperationalNotifications::class)->ready())
<div data-operational-notifications data-feed="{{ route('backend.admin.notifications.feed') }}" data-read-all="{{ route('backend.admin.notifications.read-all') }}"
    data-read-base="{{ url('/admin/notifications') }}" data-empty="{{ __('notifications.empty') }}" data-error="{{ __('notifications.error') }}"
    data-read-label="{{ __('notifications.read') }}" data-title="{{ __('notifications.title') }}" data-sign-in="{{ __('Sign in to continue') }}" data-denied="{{ __('Your account is not authorized') }}" data-login="{{ route('login') }}">
    <button type="button" data-notification-open aria-label="{{ __('notifications.title') }}" aria-haspopup="dialog" aria-controls="qpos-notification-panel"
        class="qpos-shell-control relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl">
        <x-backend.icon name="bell" /><span data-notification-badge class="absolute -top-1 -right-1 rounded-full bg-qpos-danger text-white px-1 text-xs hidden" aria-hidden="true"></span>
    </button>
    <dialog id="qpos-notification-panel" aria-labelledby="qpos-notification-title" class="qpos-notification-panel">
        <div class="flex items-center justify-between gap-3"><h2 id="qpos-notification-title" class="text-lg font-semibold">{{ __('notifications.title') }}</h2><button type="button" data-notification-close class="qpos-icon-button" aria-label="{{ __('Close') }}"><x-backend.icon name="x" /></button></div>
        <div class="flex flex-wrap gap-2 my-4"><button type="button" data-notification-read-all class="qpos-button qpos-button-sm qpos-button-secondary">{{ __('notifications.read_all') }}</button><a href="{{ route('backend.admin.notifications.preferences') }}" class="qpos-button qpos-button-sm qpos-button-ghost">{{ __('notifications.preferences') }}</a></div>
        <p data-notification-status role="status" class="text-sm text-qpos-muted"></p>
        <ol data-notification-list class="grid gap-3"></ol>
        <button type="button" data-notification-more class="qpos-button qpos-button-sm qpos-button-secondary mt-4 hidden">{{ __('notifications.more') }}</button>
    </dialog>
</div>
@vite('resources/js/notifications.js')
@endif
