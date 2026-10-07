@extends('backend.master-tailwind')
@section('title', __('Physical inventories').' #'.$inventory->id)
@section('content')
<x-backend.card>
    <p class="font-semibold">{{ __($inventory->status) }} · {{ $inventory->reason }}</p>
    <p class="my-4 text-sm text-qpos-muted">{{ __('Count each physical lot and bucket in base units. Unknown or expired lots remain blocked. Blank counts are refused. Inventory never approves opening provenance.') }}</p>
    @foreach($items as $item)
    <section class="border border-qpos-line rounded-lg p-4 mb-4">
        <h2 class="font-semibold">{{ $item->sku }} · {{ $item->name }}</h2>
        @if($inventory->status==='counting')
        <form method="post" action="{{ route('backend.admin.stock.inventories.count',[$inventory->id,$item->id]) }}" class="my-3">@csrf
            <button name="action" value="begin" class="qpos-button qpos-button-md qpos-button-secondary">{{ $item->count_started_at ? __('Restart count') : __('Begin physical count') }}</button>
        </form>
        @endif
        @if($item->count_values)@php($values=json_decode($item->count_values,true))
        <form method="post" action="{{ route('backend.admin.stock.inventories.count',[$inventory->id,$item->id]) }}" class="space-y-3">@csrf
            <input type="hidden" name="action" value="count">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>{{ __('Lot / bucket') }}</th><th>{{ __('Expected at count start') }}</th><th>{{ __('Physical count') }}</th></tr></thead><tbody>
                @foreach($values['baseline'] as $bucketKey=>$quantity)@php([$batchId,$bucket]=explode(':',$bucketKey))
                <tr><td class="p-2">{{ $batchId==='0' ? __('Without documented lot') : ($batches->get((int)$batchId)?->batch_number ?: '#'.$batchId) }} · {{ $bucket==='saleable' ? __('Saleable bucket before expiry exclusions') : __($bucket) }}
                    @if($batchId!=='0')<span class="block text-xs text-qpos-muted">{{ __($batches->get((int)$batchId)?->expiry_status ?? 'unknown') }} {{ $batches->get((int)$batchId)?->expires_on }}</span>@endif</td>
                    <td class="p-2">{{ $quantity }}</td><td class="p-2"><input name="counts[{{ $bucketKey }}]" value="{{ $values['counts'][$bucketKey] ?? '' }}" class="qpos-control w-40" inputmode="decimal" required aria-label="{{ __('Physical count') }} {{ $bucketKey }}" @disabled($inventory->status!=='counting' || $item->counted_at)></td></tr>
                @endforeach
            </tbody></table></div>
            @if($inventory->status==='counting' && !$item->counted_at)<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save completed count') }}</button>@endif
            @if($item->counted_at)<p class="text-sm">{{ __('Count completed') }} : {{ $item->counted_at }}</p>@endif
        </form>
        @endif
        @if($item->adjustments)@foreach(json_decode($item->adjustments,true) as $adjustment)<p class="text-sm mt-2">{{ __('Adjustment') }} #{{ $adjustment['movement_id'] }} · {{ $adjustment['key'] }} · {{ $adjustment['delta'] }} → {{ $adjustment['target'] }}</p>@endforeach @endif
    </section>
    @endforeach
    @if($inventory->status==='counting')
    <div class="flex flex-wrap gap-3">
        <form method="post" action="{{ route('backend.admin.stock.inventories.validate',$inventory->id) }}">@csrf<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Validate inventory and adjustments') }}</button></form>
        <form method="post" action="{{ route('backend.admin.stock.inventories.cancel',$inventory->id) }}">@csrf<button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Cancel inventory') }}</button></form>
    </div>
    @endif
</x-backend.card>
@endsection
