@extends('backend.master-tailwind')
@section('title',__('Pending sales'))
@section('content')
<x-backend.card :title="__('Pending sales')"><p>{{ $shop->name }} · {{ __('Pending sales do not reserve stock.') }}</p>
<div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Seller') }}</th><th>{{ __('Cashier') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th><th>{{ __('Expiry') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@foreach($rows as $p)<tr><td>{{ $p->id }}</td><td>{{ $p->seller }}</td><td>{{ $p->cashier??'—' }}</td><td>{{ $p->customer==='Walking Customer'?__('Walking Customer'):$p->customer }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($p->proposed_total) }} XAF</td><td>{{ __($p->state) }}</td><td>{{ Carbon\CarbonImmutable::parse($p->expires_at,'UTC')->timezone('Africa/Douala')->format('d/m H:i') }}</td><td>
@if($p->state==='pending'&&auth()->user()->can('pending_sale_collect'))<form method="post" action="{{ route('backend.admin.pending.take',$p->id) }}">@csrf @include('backend.phase4.context')<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Take sale') }}</button></form>@endif
@if($p->state==='taken'&&(int)$p->cashier_user_id===auth()->id())<a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.cart.index',['pending_sale_id'=>$p->id,'operation_point_of_sale_id'=>$shop->id]) }}">{{ __('Resume') }}</a>@endif
@if(in_array($p->state,['pending','taken']))<form method="post" action="{{ route('backend.admin.pending.resolve',$p->id) }}" class="grid gap-2 mt-2">@csrf @include('backend.phase4.context')<input class="qpos-control" name="reason" required maxlength="500" aria-label="{{ __('Reason') }}">
@if($p->state==='taken'&&(int)$p->cashier_user_id===auth()->id())<button name="action" value="release" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Return to seller') }}</button>@endif
@if(($p->state==='pending'&&(int)$p->seller_user_id===auth()->id())||auth()->user()->hasRole('Admin'))<button name="action" value="cancel" class="qpos-button qpos-button-md qpos-button-danger">{{ __('Cancel') }}</button>@endif</form>@endif
@if($p->completed_order_id)<a href="{{ route('backend.admin.orders.show',$p->completed_order_id) }}">{{ __('Sale') }} #{{ $p->completed_order_id }}</a>@endif
</td></tr>@endforeach</tbody></table></div>{{ $rows->links() }}</x-backend.card>
@endsection
