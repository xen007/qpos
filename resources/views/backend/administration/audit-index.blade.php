@extends('backend.master-tailwind')
@section('title', __('administration.audit'))
@section('content')
    <x-backend.card :title="__('Recent account activity')">
        <p class="text-sm text-qpos-muted">{{ __('Last observed authorized request, updated at most once a minute. Recent activity does not prove that a browser is still connected. Login and logout events are listed in the audit below.') }}</p>
        <ul class="mt-3 space-y-2">
        @forelse($activity as $a)<li>{{ $a->name }} #{{ $a->user_id }} · {{ $a->shop??__('administration.global_event') }} · {{ \Carbon\CarbonImmutable::parse($a->last_seen_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }} @if($a->is_suspended) · {{ __('Suspended') }}@endif</li>
        @empty<li>{{ __('No recent account activity recorded.') }}</li>@endforelse
        </ul>
    </x-backend.card>
    <x-backend.card>
        <form method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-backend.input name="from" type="date" :label="__('administration.from')" :value="$filters['from'] ?? ''" />
            <x-backend.input name="until" type="date" :label="__('administration.until')" :value="$filters['until'] ?? ''" />
            <label class="text-sm font-medium" for="audit-actor">{{ __('administration.actor') }}
                <select class="qpos-control mt-1 w-full" id="audit-actor" name="actor_id">
                    <option value="">{{ __('administration.all') }}</option>
                    @foreach($actors as $actor)<option value="{{ $actor->id }}" @selected((string)($filters['actor_id']??'') === (string)$actor->id)>{{ $actor->name }}@if($actor->name !== '#'.$actor->id) (#{{ $actor->id }})@endif</option>@endforeach
                    @if(!empty($filters['actor_id']) && !$actors->contains('id',(int)$filters['actor_id']))<option value="{{ $filters['actor_id'] }}" selected>#{{ $filters['actor_id'] }}</option>@endif
                </select>
            </label>
            <label class="text-sm font-medium" for="audit-shop">{{ __('Stores') }}
                <select class="qpos-control mt-1 w-full" id="audit-shop" name="point_of_sale_id">
                    <option value="">{{ __('administration.all') }}</option>
                    @foreach($shops as $shop)<option value="{{ $shop->id }}" @selected((string)($filters['point_of_sale_id']??'') === (string)$shop->id)>{{ $shop->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-medium" for="audit-event">{{ __('administration.event') }}
                <select class="qpos-control mt-1 w-full" id="audit-event" name="event">
                    <option value="">{{ __('administration.all') }}</option>
                    @foreach($eventChoices as $choice)<option value="{{ $choice }}" @selected(($filters['event']??'') === $choice)>{{ \Illuminate\Support\Facades\Lang::has('administration.events.'.$choice) ? __('administration.events.'.$choice) : $choice }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-medium" for="audit-object">{{ __('administration.object') }}
                <select class="qpos-control mt-1 w-full" id="audit-object" name="object_type">
                    <option value="">{{ __('administration.all') }}</option>
                    @foreach($objectChoices as $choice)<option value="{{ $choice }}" @selected(($filters['object_type']??'') === $choice)>{{ __('administration.objects.'.$choice) }}</option>@endforeach
                </select>
            </label>
            <div class="flex flex-wrap items-end gap-2 sm:col-span-2">
                <button type="submit" class="qpos-button qpos-button-md qpos-button-primary">{{ __('Apply') }}</button>
                <a class="qpos-button qpos-button-md" href="{{ route('backend.admin.audit.index') }}">{{ __('administration.reset_filters') }}</a>
            </div>
        </form>
    </x-backend.card>
    <div class="space-y-3">
        @forelse($events as $event)
            <x-backend.card>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 space-y-1 break-words">
                        <p class="font-semibold">{{ \Illuminate\Support\Facades\Lang::has('administration.events.'.$event->event) ? __('administration.events.'.$event->event) : $event->event }}</p>
                        <p class="text-sm text-qpos-muted">{{ $event->recorded_at->timezone('Africa/Douala')->format('d/m/Y H:i:s') }} · {{ $event->actor_name ?? ($event->actor_id === null ? __('administration.unknown_actor') : '#'.$event->actor_id) }} · {{ $event->shop_name ?? __('administration.global_event') }}</p>
                        <p class="text-sm">{{ __('administration.objects.'.$event->object_type) }} #{{ $event->object_id ?? '—' }}</p>
                    </div>
                    <a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.audit.show',$event->id) }}">{{ __('View') }}</a>
                </div>
            </x-backend.card>
        @empty
            <x-backend.card><p class="text-sm text-qpos-muted">{{ __('administration.no_events') }}</p></x-backend.card>
        @endforelse
    </div>
    <div class="mt-5">{{ $events->links('pagination::tailwind') }}</div>
@endsection
