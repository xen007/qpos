@extends('backend.master-tailwind')
@section('title',__('Cash supervision'))
@section('content')
<x-backend.card :title="__('Cash supervision')"><p>{{ __('Paused sessions are listed separately from inactive sessions. Daily summaries never close cash sessions.') }}</p>
<div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead><tr><th>{{ __('Store') }}</th><th>{{ __('Cashier') }}</th><th>{{ __('Last activity') }}</th><th>{{ __('Opening cash') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@foreach($rows as $s)<tr><td>{{ $s->shop }}</td><td>{{ $s->cashier }} #{{ $s->id }}</td><td>{{ Carbon\CarbonImmutable::parse($s->last_activity,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($s->opening_amount) }} XAF</td><td>{{ $s->paused?__('Paused'):($s->suspected?__('Inactive session'):__('Open')) }}</td><td>
@if($s->suspected)<form method="post" action="{{ route('backend.admin.cash.supervised-close',$s->id) }}" class="grid gap-2">@csrf<input type="hidden" name="operation_point_of_sale_id" value="{{ $s->point_of_sale_id }}"><input type="hidden" name="operation_key" value="{{ Illuminate\Support\Str::uuid() }}"><label>{{ __('Counted cash') }}<input name="counted_amount" type="number" min="0" step="1" required class="qpos-control"></label><label>{{ __('Reason') }}<input name="reason" maxlength="500" required class="qpos-control"></label><button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Supervised close') }}</button></form>@endif
</td></tr>@endforeach</tbody></table></div>{{ $rows->links() }}</x-backend.card>
@endsection
