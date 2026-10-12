@extends('backend.master-tailwind')
@section('title', __('administration.audit_detail'))
@section('page-actions')
    <a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.audit.index') }}">{{ __('Back') }}</a>
@endsection
@section('content')
    <x-backend.card>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.event') }}</dt><dd>{{ \Illuminate\Support\Facades\Lang::has('administration.events.'.$event->event) ? __('administration.events.'.$event->event) : $event->event }}</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.object') }}</dt><dd>{{ __('administration.objects.'.$event->object_type) }} #{{ $event->object_id ?? '—' }}</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.actor') }}</dt><dd>{{ $event->actor_name ?? __('administration.unknown_actor') }} @if($event->actor_id !== null) (#{{ $event->actor_id }}) @endif</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.result') }}</dt><dd>{{ $event->result === 'success' ? __('administration.success') : $event->result }}</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('Stores') }}</dt><dd>{{ $event->shop_name ?? __('administration.global_event') }}</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.recorded_at') }}</dt><dd>{{ $event->recorded_at->timezone('Africa/Douala')->format('d/m/Y H:i:s') }} (Africa/Douala)</dd></div>
            <div><dt class="text-sm text-qpos-muted">{{ __('administration.correlation') }}</dt><dd class="break-all font-mono text-xs">{{ $event->correlation_id }}</dd></div>
        </dl>
    </x-backend.card>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach(['old_values'=>'before','new_values'=>'after'] as $field=>$label)
            <x-backend.card :title="__('administration.'.$label)">
                <pre class="whitespace-pre-wrap break-words text-xs">{{ json_encode($event->$field ?? [],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
            </x-backend.card>
        @endforeach
    </div>
@endsection
