@extends('backend.master-tailwind')
@section('title',__('replenishment.policies'))
@section('content')
<div class="space-y-6 qpos-touch-workspace">
<x-backend.card :title="__('replenishment.policies')">
<p>{{ __('replenishment.policy_help') }}</p>
<form method="get" class="flex flex-wrap gap-3 mt-4">
<input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}">
<x-backend.input name="search" :label="__('Search')" :value="request('search')" />
<x-backend.select name="scope" :label="__('replenishment.scope')" :options="App\Support\RoleAssignment::globalManager(auth()->user())?['product_shop'=>__('replenishment.product_shop'),'shop'=>__('Shop'),'product'=>__('Product'),'global'=>__('replenishment.global')]:['product_shop'=>__('replenishment.product_shop'),'shop'=>__('Shop')]" :selected="$scope" />
<button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Search') }}</button>
</form>
<div class="flex flex-wrap gap-2 mt-4">@foreach($products as $p)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.policies',['product_id'=>$p->id,'scope'=>$scope,'operation_point_of_sale_id'=>$shop->id]) }}">{{ $p->name }}</a>@endforeach</div>
{{ $products->links() }}
</x-backend.card>
@if($product || in_array($scope,['global','shop']))
<x-backend.card :title="$product?->name??__('replenishment.defaults')">
<form method="post" class="grid gap-4 md:grid-cols-2" action="{{ route('backend.admin.replenishment.policies.save') }}">@csrf
<input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}"><input type="hidden" name="scope" value="{{ $scope }}"><input type="hidden" name="product_id" value="{{ $product?->id }}">
@foreach(['minimum','optimal','maximum'] as $f)
<div><x-backend.input :name="$f" :label="__('replenishment.'.$f)" type="number" step="0.000001" min="0" :value="old($f,$stored?->$f)" />
<p class="text-sm">{{ __('replenishment.effective') }}: {{ $effective[$f]===null?'—':App\Support\SaleFormat::quantityDisplay($effective[$f]) }} · {{ $effective['origins'][$f] }}</p></div>
@endforeach
@foreach(['alerts_enabled','maximum_enabled','hide_out_of_stock'] as $f)
<div><x-backend.select :name="$f" :label="__('replenishment.'.$f)" :options="[''=>__('replenishment.inherit'),'1'=>__('Yes'),'0'=>__('No')]" :selected="old($f,$stored?->$f===null?'':(string)(int)$stored->$f)" />
<p class="text-sm">{{ __('replenishment.effective') }}: {{ $effective[$f]?__('Yes'):__('No') }} · {{ $effective['origins'][$f] }}</p></div>
@endforeach
<x-backend.input name="reason" :label="__('Reason')" required maxlength="500" />
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button>
</form>
</x-backend.card>
@endif
</div>
@endsection
