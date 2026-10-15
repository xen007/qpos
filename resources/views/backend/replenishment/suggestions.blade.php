@extends('backend.master-tailwind')
@section('title',__('replenishment.suggestions'))
@section('content')
<div class="space-y-6 qpos-touch-workspace">
<x-backend.card :title="__('replenishment.suggestions')">
<p>{{ $shop->name }} · {{ __('replenishment.suggestion_help') }}</p>
<div class="flex flex-wrap gap-3 my-4">
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.settings',['operation_point_of_sale_id'=>$shop->id]) }}">{{ __('Settings') }}</a>
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.runs',['operation_point_of_sale_id'=>$shop->id]) }}">{{ __('History') }}</a>
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.create',['operation_point_of_sale_id'=>$shop->id]) }}">{{ __('replenishment.manual_order') }}</a>
</div>
<form method="get" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
<input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}">
<x-backend.select name="supplier_id" :label="__('Supplier')" :options="[''=>__('All')]+$suppliers" :selected="$filters['supplier_id']??''"/>
<x-backend.select name="category_id" :label="__('Category')" :options="[''=>__('All')]+$categories" :selected="$filters['category_id']??''"/>
<x-backend.input name="search" :label="__('Search')" :value="$filters['search']??''" maxlength="100"/>
<x-backend.select name="window_days" :label="__('replenishment.window')" :options="[30=>'30',60=>'60',90=>'90']" :selected="$filters['window_days']"/>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Filter') }}</button>
</form>
</x-backend.card>
<x-backend.card :title="__('replenishment.proposals')">
<form method="post" action="{{ route('backend.admin.replenishment.generate') }}" class="space-y-4" id="suggestion-selection">
@csrf
<input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}">
<input type="hidden" name="operation_key" value="{{ old('operation_key',$key) }}">
<input type="hidden" name="window_days" value="{{ $filters['window_days'] }}">
@if(!empty($filters['supplier_id']))<input type="hidden" name="supplier_id" value="{{ $filters['supplier_id'] }}">@endif
<div class="flex flex-wrap gap-3">
<button type="button" data-select-products="1" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('replenishment.select_page') }}</button>
<button type="button" data-select-products="0" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('replenishment.clear_page') }}</button>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('replenishment.generate_drafts') }}</button>
</div>
@forelse($rows as $row)
<fieldset class="qpos-card p-4 space-y-3">
<legend class="font-semibold"><label class="inline-flex items-center gap-3 min-h-11">
<input type="checkbox" name="product_ids[]" value="{{ $row['product_id'] }}" @disabled(!$row['can_draft']||!empty($row['existing_draft_id']))>
{{ $row['name'] }} · {{ $row['sku'] }}</label></legend>
<p>{{ $row['supplier_name']??__('replenishment.missing_supplier') }} · {{ $row['unit_label']??'—' }}</p>
<p>{{ __('replenishment.estimated_cost') }}: {{ $row['unit_price']===null?'—':App\Support\SaleFormat::moneyDisplay($row['unit_price']).' XAF' }} · {{ __('replenishment.price_on') }}: {{ $row['price_on']??'—' }}</p>
<dl class="grid gap-2 md:grid-cols-3 xl:grid-cols-4">
@foreach(['available','incoming','late_incoming','undated_incoming','overdue_incoming','target','need','quantity_packs','quantity_base','daily'] as $field)
<div><dt>{{ __('replenishment.'.$field) }}</dt><dd class="font-semibold">{{ App\Support\SaleFormat::quantityDisplay($row[$field]) }}</dd></div>
@endforeach
</dl>
<p>{{ __('replenishment.observed_days') }}: {{ $row['observed_days'] }}/{{ $row['window_days'] }} · {{ __('replenishment.horizon') }}: {{ $row['horizon_days'] }} · {{ __('replenishment.delay_days') }}: {{ $row['delay_days']??'—' }}</p>
@if(!empty($row['existing_draft_id']))
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.show',$row['existing_draft_id']) }}">{{ __('replenishment.existing_draft') }}</a>
@endif
@foreach($row['warnings'] as $warning)<p class="text-amber-700 dark:text-amber-300">{{ __('replenishment.warning_'.$warning) }}</p>@endforeach
@foreach($row['transfers'] as $transfer)<p>{{ __('replenishment.transfer_option',['shop'=>$transfer['shop_name'],'quantity'=>App\Support\SaleFormat::quantityDisplay($transfer['quantity_base'])]) }}</p>@endforeach
</fieldset>
@empty<p>{{ __('No records found') }}</p>@endforelse
</form>
@if($next)<a class="qpos-button qpos-button-md qpos-button-secondary mt-4" href="{{ route('backend.admin.replenishment.suggestions',array_merge(request()->query(),['after'=>$next])) }}">{{ __('Next') }}</a>@endif
<p class="mt-4">{{ __('replenishment.preview_limit') }}</p>
</x-backend.card>
</div>
@endsection
@push('script')
<script>
document.querySelectorAll('[data-select-products]').forEach(button=>button.addEventListener('click',()=>{
 document.querySelectorAll('#suggestion-selection input[name="product_ids[]"]:not(:disabled)').forEach(input=>{input.checked=button.dataset.selectProducts==='1';});
}));
</script>
@endpush
