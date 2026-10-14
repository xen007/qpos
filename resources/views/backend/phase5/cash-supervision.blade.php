@extends('backend.master-tailwind')
@section('title',__('Cash supervision'))
@section('content')
<x-backend.card :title="__('Cash supervision')">
<p>{{ __('Open sessions include active and paused cashiers. Closed sessions are available in history. Daily summaries never close cash sessions.') }}</p>
<form method="get" class="grid gap-3 my-4 md:grid-cols-4">
<x-backend.select name="state" :label="__('Status')" :options="['open'=>__('Open sessions'),'closed'=>__('Closed sessions')]" :selected="$state" :restoreOld="false" />
<x-backend.select name="shop" :label="__('Store')" :options="$stores->pluck('name','id')->all()" :placeholder="__('All accessible stores')" :selected="$filters['shop']??null" :restoreOld="false" />
<x-backend.select name="cashier" :label="__('Cashier')" :options="$cashiers->all()" :placeholder="__('All cashiers')" :selected="$filters['cashier']??null" :restoreOld="false" />
<button class="qpos-button qpos-button-md qpos-button-primary self-end">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead><tr><th>{{ __('Store') }}</th><th>{{ __('Cashier') }}</th><th>{{ $state==='open'?__('Last activity'):__('Closed at') }}</th><th>{{ __('Opening cash') }}</th><th>{{ __('Status') }}</th><th>{{ $state==='open'?__('Actions'):__('Cash difference') }}</th></tr></thead><tbody>
@forelse($rows as $s)<tr><td>{{ $s->shop }}</td><td>{{ $s->cashier }} #{{ $s->id }}</td><td>{{ Carbon\CarbonImmutable::parse($state==='open'?$s->last_activity:$s->closed_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($s->opening_amount) }} XAF</td><td>{{ $state==='closed'?__('Closed'):($s->paused?__('Paused'):($s->suspected?__('Inactive session'):__('Open'))) }}</td><td>
@if($state==='closed')
{{ App\Support\SaleFormat::moneyDisplay($s->difference) }} XAF
@elseif($s->suspected)
<form method="post" action="{{ route('backend.admin.cash.supervised-close',$s->id) }}" class="grid gap-2">@csrf<input type="hidden" name="operation_point_of_sale_id" value="{{ $s->point_of_sale_id }}"><input type="hidden" name="operation_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>{{ __('Counted cash') }}<input name="counted_amount" type="number" min="0" step="1" required class="qpos-control"></label><label>{{ __('Reason') }}<input name="reason" maxlength="500" required class="qpos-control"></label><button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Supervised close') }}</button></form>
@endif
</td></tr>@empty<tr><td colspan="6" class="p-4">{{ $state==='open'?__('No open cash sessions for these filters. Consult closed sessions to see previous closures.'):__('No closed cash sessions for these filters.') }}</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}</x-backend.card>
@endsection
