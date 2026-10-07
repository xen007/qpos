@extends('backend.master-tailwind')
@section('title', __('Openings awaiting approval'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('These quantities are unavailable for sale. Only a reasoned approval with lot, cost and expiry evidence can reclassify them. Partial approval leaves the remainder blocked.') }}</p>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>SKU</th><th>{{ __('Product') }}</th><th>{{ __('Blocked quantity') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
        @forelse($products as $stock)<tr><td class="p-3">{{ $stock->product->sku }}</td><td class="p-3">{{ $stock->product->name }}</td><td class="p-3">{{ $stock->unallocated_opening_quantity }}</td><td class="p-3"><a class="text-qpos-brand" href="{{ route('backend.admin.stock.openings.show',$stock->product_id) }}">{{ __('Review opening') }}</a></td></tr>
        @empty<tr><td colspan="4" class="p-3">{{ __('No blocked openings in this shop.') }}</td></tr>@endforelse
    </tbody></table></div>{{ $products->links() }}
    <h2 class="font-semibold mt-6">{{ __('Recent reasoned approvals') }}</h2>
    @foreach($approvals as $approval)<p class="mt-3 text-sm">#{{ $approval->id }} · {{ $approval->name }} · {{ $approval->quantity }} · {{ $approval->unit_cost }} {{ $approval->currency_code }} · {{ $approval->reason }} · {{ $approval->evidence }} · {{ $approval->approved_at }}</p>@endforeach
</x-backend.card>
@endsection
