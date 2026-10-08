<x-backend.card :title="__('reporting.filters')">
    <form method="get" class="flex flex-wrap items-end gap-3">
        <label class="text-sm">{{ __('reporting.shop') }}
            <select name="shop_id" class="qpos-control block w-full"><option value="">{{ __('reporting.all_authorized') }}</option>
                @foreach($shops as $shop)<option value="{{ $shop->id }}" @selected((string)request('shop_id') === (string)$shop->id)>{{ $shop->name }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm">{{ __('reporting.period') }}<select name="period" class="qpos-control block w-full">
            @foreach(['day','week','month','custom'] as $p)<option value="{{ $p }}" @selected($filter->period === $p)>{{ __('reporting.'.$p) }}</option>@endforeach
        </select></label>
        <label class="text-sm">{{ __('reporting.anchor_date') }}<input type="date" name="date" value="{{ request('date', now('Africa/Douala')->toDateString()) }}" class="qpos-control block"></label>
        <label class="text-sm">{{ __('reporting.date_from') }}<input type="date" name="date_from" value="{{ $filter->start->toDateString() }}" class="qpos-control block"></label>
        <label class="text-sm">{{ __('reporting.date_to') }}<input type="date" name="date_to" value="{{ $filter->end->subDay()->toDateString() }}" class="qpos-control block"></label>
        @if(!isset($report) || in_array($report['type'], ['sales','seller','shop','category','product','peaks','stock','expiry']))
            @if(!isset($report) || !in_array($report['type'], ['stock','expiry']))
                <label class="text-sm">{{ __('reporting.seller') }}<select name="seller_id" class="qpos-control block"><option value="">{{ __('All') }}</option>@foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected($filter->seller === $seller->id)>{{ $seller->name }}</option>@endforeach</select></label>
            @endif
            <label class="text-sm">{{ __('reporting.category') }}<select name="category_id" class="qpos-control block"><option value="">{{ __('All') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($filter->category === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="text-sm">{{ __('reporting.product_id') }}<input type="number" min="1" list="report-products" name="product_id" value="{{ $filter->product }}" class="qpos-control block"><datalist id="report-products">@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</datalist></label>
        @endif
        @if(($report['type'] ?? '') === 'expiry')
            <label class="text-sm">{{ __('reporting.expiry') }}<select name="expiry" class="qpos-control block">@foreach(['expired','7','30','90','unknown'] as $expiry)<option value="{{ $expiry }}" @selected(request('expiry','90') === $expiry)>{{ __('reporting.expiry_'.$expiry) }}</option>@endforeach</select></label>
        @endif
        <button class="qpos-button qpos-button-primary qpos-button-md">{{ __('Apply') }}</button>
    </form>
    <p class="mt-3 text-sm text-qpos-muted">{{ $filter->label() }} · {{ __('reporting.custom_hint') }}</p>
</x-backend.card>
