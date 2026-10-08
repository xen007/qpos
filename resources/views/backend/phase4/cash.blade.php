@extends('backend.master-tailwind')
@section('title', __('Cash sessions'))
@section('content')
<div class="space-y-6">
<x-backend.card :title="__('Cash sessions')">
<p>{{ $shop->name }} · XAF · {{ auth()->user()->name }}</p>
@if(!$active)
<form method="POST" action="{{ route('backend.admin.cash.open') }}" class="grid gap-4 mt-4">@csrf @include('backend.phase4.context')
<x-backend.input name="opening_amount" :label="__('Opening cash')" type="number" min="0" step="1" required />
<x-backend.select name="handover_from_id" :label="__('Previous handover')" :options="$handovers->mapWithKeys(fn($s)=>[$s->id=>'#'.$s->id.' · '.$s->counted_amount.' XAF'])->all()" :placeholder="__('None')" />
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Open session') }}</button></form>
@else
<p class="mt-4">#{{ $active->id }} · {{ __('Opening cash') }}: {{ App\Support\SaleFormat::xaf($active->opening_amount) }} · {{ __('Expected cash') }}: {{ App\Support\SaleFormat::xaf(app(App\Services\CashService::class)->expected($active)) }}</p>
<form method="POST" action="{{ route('backend.admin.cash.close',$active->id) }}" class="grid gap-4 mt-4">@csrf @include('backend.phase4.context')
<x-backend.input name="counted_amount" :label="__('Counted cash')" type="number" min="0" step="1" required />
<x-backend.select name="handover_to_user_id" :label="__('Next cashier')" :options="$cashiers->where('id','!=',auth()->id())->pluck('name','id')->all()" :placeholder="__('None')" />
<x-backend.input name="reason" id="cash-close-reason" :label="__('Reason for difference')" maxlength="500" />
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Close session') }}</button></form>
@can('cash_movement_create')
<form method="POST" action="{{ route('backend.admin.cash.movements') }}" class="grid gap-4 mt-6">@csrf @include('backend.phase4.context')
<x-backend.select name="direction" :label="__('Cash direction')" :options="['in'=>__('Cash in'),'out'=>__('Cash out')]" />
<x-backend.input name="amount" :label="__('Amount')" type="number" min="1" step="1" required />
<x-backend.input name="reason" id="cash-movement-reason" :label="__('Reason')" maxlength="500" required /><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Record movement') }}</button></form>
@endcan
<div class="overflow-x-auto mt-6"><table class="w-full text-sm"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Cash direction') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Reason') }}</th></tr></thead><tbody>
@foreach($movements as $m)<tr><td>{{ Illuminate\Support\Carbon::parse($m->occurred_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ __($m->direction) }}</td><td>{{ App\Support\SaleFormat::xaf($m->amount) }}</td><td>{{ $m->reason }}</td></tr>@endforeach
</tbody></table></div>{{ $movements->links() }}
@endif
</x-backend.card>
<x-backend.card :title="__('Session history')"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Status') }}</th><th>{{ __('Opening cash') }}</th><th>{{ __('Expected cash') }}</th><th>{{ __('Counted cash') }}</th><th>{{ __('Difference') }}</th></tr></thead><tbody>
@foreach($sessions as $s)<tr><td>{{ $s->id }}</td><td>{{ __($s->state) }}</td><td>{{ App\Support\SaleFormat::xaf($s->opening_amount) }}</td><td>{{ $s->expected_amount === null ? '—' : App\Support\SaleFormat::xaf($s->expected_amount) }}</td><td>{{ $s->counted_amount === null ? '—' : App\Support\SaleFormat::xaf($s->counted_amount) }}</td><td>{{ $s->difference === null ? '—' : App\Support\SaleFormat::xaf($s->difference) }}</td></tr>@endforeach
</tbody></table></div>{{ $sessions->links() }}</x-backend.card>
</div>
@endsection
