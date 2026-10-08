<nav aria-label="{{ __('Reports') }}" class="my-4 flex flex-wrap gap-2">
    @foreach($types as $name => $permission)
        @if(auth()->user()->can($permission) && ($name !== 'history' || auth()->user()->hasRole('Admin')))
            <a class="qpos-button qpos-button-secondary qpos-button-sm" href="{{ route('backend.admin.reporting.index', ['type'=>$name]) }}">{{ __('reporting.'.$name) }}</a>
        @endif
    @endforeach
    @can('daily_summary_view')<a class="qpos-button qpos-button-secondary qpos-button-sm" href="{{ route('backend.admin.reporting.summaries') }}">{{ __('reporting.daily_summary') }}</a>@endcan
</nav>
