@extends('backend.master-tailwind')
@section('title', __('reporting.workday'))
@section('content')
<p class="mb-4 text-qpos-muted">{{ __('reporting.three_levels') }}</p>
<x-backend.card :title="__('reporting.pause')">
<form method="post" action="{{ route('backend.admin.reporting.pause') }}" class="flex flex-wrap gap-3">@csrf
<select name="shop_id" aria-label="{{ __('reporting.shop') }}" class="qpos-control">@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select>
<input type="hidden" name="action" value="{{ $pause ? 'resume' : 'pause' }}"><button class="qpos-button qpos-button-primary qpos-button-md">{{ __('reporting.'.($pause ? 'resume' : 'pause')) }}</button>
<a class="qpos-button qpos-button-secondary qpos-button-md" href="{{ route('backend.admin.cash.index') }}">{{ __('reporting.manage_session') }}</a>
@can('daily_summary_view')<a class="qpos-button qpos-button-secondary qpos-button-md" href="{{ route('backend.admin.reporting.summaries') }}">{{ __('reporting.daily_summary') }}</a>@endcan
</form>
</x-backend.card>
<p class="my-4">{{ __('reporting.closure_notice') }}</p>
<x-backend.card :title="__('Cash sessions')"><div class="overflow-x-auto"><table class="qpos-table w-full"><thead><tr><th>ID</th><th>{{ __('reporting.shop_id') }}</th><th>{{ __('reporting.state') }}</th><th>{{ __('reporting.opening_amount') }}</th></tr></thead><tbody>@foreach($sessions as $s)<tr><td class="p-3">{{ $s->id }}</td><td class="p-3">{{ $s->point_of_sale_id }}</td><td class="p-3">{{ $s->state }}</td><td class="p-3">{{ $s->opening_amount }}</td></tr>@endforeach</tbody></table></div>{{ $sessions->links('pagination::tailwind') }}</x-backend.card>
@endsection
