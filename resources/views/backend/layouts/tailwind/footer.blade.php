<footer class="border-t border-qpos-line px-4 py-4 text-sm text-qpos-muted sm:px-6 lg:px-8 print:hidden">
    <strong class="font-semibold text-qpos-ink">
        &copy; {{ date('Y') }}
        <a href="{{ readConfig('site_url') }}" class="transition hover:text-qpos-brand">{{ readConfig('site_name') }}</a>
    </strong>
    {{ __('All rights reserved.') }}
</footer>
