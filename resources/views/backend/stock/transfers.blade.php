@extends('backend.master-tailwind')
@section('title', __('Stock transfers'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Dispatch removes source availability. Destination availability begins only at physical receipt.') }}</p>
    @can('stock_transfer_dispatch')
    <form method="post" action="{{ route('backend.admin.stock.transfers.create') }}" class="space-y-4">
        @csrf <input type="hidden" name="operation_key" value="{{ $key }}">
        @if($selectedPointOfSale)<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale->id }}"><p>{{ __('Source') }} : {{ $selectedPointOfSale->name }}</p>@endif
        <label class="block">{{ __('Destination') }}<select name="destination_shop_id" required class="qpos-control mt-1 w-full">
            <option value="">{{ __('Choose destination') }}</option>
            @foreach($shops as $shop)@if($shop->id!==$selectedPointOfSale?->id)<option value="{{ $shop->id }}">{{ $shop->code }} · {{ $shop->name }}</option>@endif @endforeach
        </select></label>
        <p class="text-sm">{{ __('Quantities are expressed in base units.') }}</p>
        <div class="space-y-2" id="transfer-lines"><div class="grid gap-2 lg:grid-cols-2" data-transfer-line>
            <label>{{ __('Product') }}<select required name="items[0][product_id]" class="qpos-control w-full"><option value="">{{ __('Select product') }}</option>
                @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->sku }} · {{ $product->name }}</option>@endforeach
            </select></label>
            <label>{{ __('Quantity') }}<input required name="items[0][quantity]" inputmode="decimal" class="qpos-control w-full" placeholder="0.000000"></label>
        </div></div>
        <button type="button" class="qpos-button qpos-button-md qpos-button-secondary" id="add-transfer-line">{{ __('Add product') }}</button>
        <label class="block">{{ __('Reason') }}<textarea required maxlength="5000" name="reason" class="qpos-control mt-1 w-full"></textarea></label>
        <button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Create draft transfer') }}</button>
    </form>
    @endcan
    <div class="overflow-x-auto mt-6"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Source') }}</th><th>{{ __('Destination') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @foreach($transfers as $transfer)<tr><td class="p-3"><a class="text-qpos-brand" href="{{ route('backend.admin.stock.transfers.show',$transfer->id) }}">#{{ $transfer->id }}</a></td>
            <td class="p-3">{{ $shops->firstWhere('id',$transfer->source_shop_id)?->name ?? $transfer->source_shop_id }}</td><td class="p-3">{{ $shops->firstWhere('id',$transfer->destination_shop_id)?->name ?? $transfer->destination_shop_id }}</td><td class="p-3">{{ __($transfer->status) }}</td></tr>@endforeach
    </tbody></table></div>
    {{ $transfers->links() }}
</x-backend.card>
<script>
document.getElementById('add-transfer-line')?.addEventListener('click', () => {
    const host = document.getElementById('transfer-lines');
    const count = host.children.length;
    if (count >= 200) return;
    const row = host.firstElementChild.cloneNode(true);
    row.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace('[0]', '[' + count + ']');
        input.value = '';
    });
    host.appendChild(row);
});
</script>
@endsection
