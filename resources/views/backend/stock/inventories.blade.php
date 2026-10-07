@extends('backend.master-tailwind')
@section('title', __('Physical inventories'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Sales continue. A movement during physical counting requires a recount; movements after a completed count are reconciled at validation.') }}</p>
    <form method="post" action="{{ route('backend.admin.stock.inventories.create') }}" class="space-y-4">
        @csrf<input type="hidden" name="operation_key" value="{{ $key }}">
        @if($selectedPointOfSale)<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale->id }}">@endif
        <fieldset><legend>{{ __('Products to count') }}</legend><div class="grid gap-2 lg:grid-cols-3 max-h-80 overflow-y-auto mt-2">
            @foreach($products as $product)<label class="flex gap-2 text-sm"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}">{{ $product->sku }} · {{ $product->name }}</label>@endforeach
        </div></fieldset>
        <label class="block">{{ __('Reason') }}<textarea required maxlength="5000" name="reason" class="qpos-control mt-1 w-full"></textarea></label>
        <button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Start inventory') }}</button>
    </form>
    <div class="overflow-x-auto mt-6"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Status') }}</th><th>{{ __('Started') }}</th><th>{{ __('Reason') }}</th></tr></thead><tbody>
        @foreach($inventories as $inventory)<tr><td class="p-3"><a class="text-qpos-brand" href="{{ route('backend.admin.stock.inventories.show',$inventory->id) }}">#{{ $inventory->id }}</a></td><td class="p-3">{{ __($inventory->status) }}</td><td class="p-3">{{ $inventory->started_at }}</td><td class="p-3">{{ $inventory->reason }}</td></tr>@endforeach
    </tbody></table></div>{{ $inventories->links() }}
</x-backend.card>
@endsection
