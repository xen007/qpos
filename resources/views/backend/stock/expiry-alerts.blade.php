@extends('backend.master-tailwind')
@extends('backend.master-tailwind')
@section('title', __('Expiry alerts'))
@section('content')
<x-backend.card>
    <h1 class="mb-4">{{ __('Expiry alerts') }}</h1>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>{{ __('Product') }}</th><th>SKU</th><th>{{ __('Lot') }}</th><th>{{ __('Expiry date') }}</th><th>{{ __('Available') }}</th><th>{{ __('Alert') }}</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td class="p-2">{{ $row->name }}</td><td class="p-2">{{ $row->sku }}</td><td class="p-2">{{ $row->batch_number }} @if($row->estimated_expiry)<span class="rounded bg-blue-100 px-2 py-1">{{ __('Estimated') }}</span>@endif</td><td class="p-2">{{ $row->expires_on }}</td><td class="p-2">{{ $row->saleable_quantity }}</td><td class="p-2"><span class="rounded bg-orange-100 px-2 py-1">{{ $row->alert }}</span></td></tr>
    @empty<tr><td colspan="6" class="p-3">{{ __('No expiry alerts.') }}</td></tr>@endforelse
    </tbody></table></div>
</x-backend.card>
@endsection
