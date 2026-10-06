@extends('backend.master-tailwind')
@section('title', __('Supplier'))
@section('content')
<x-backend.card :title="$supplier->name">
    <p>{{ $supplier->phone ?? '—' }} · {{ $supplier->address ?? '—' }}</p>
    <p class="mt-2 text-sm text-qpos-muted">{{ $supplier->is_active ? __('Active') : __('Inactive') }} @if($supplier->is_internal) · {{ __('Internal supplier') }} @endif</p>
</x-backend.card>
@if($purchases)
<x-backend.card :title="__('Purchases and supplier balances')" :padded="false">
    <div class="overflow-x-auto p-4">
        <table class="qpos-table">
            <thead><tr><th>{{ __('Purchase') }}</th><th>{{ __('Shop') }}</th><th>{{ __('Total') }}</th><th>{{ __('Amount due') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Receipt status') }}</th></tr></thead>
            <tbody>
            @foreach($purchases as $purchase)
                <tr><td><a class="text-qpos-brand underline" href="{{ route('backend.admin.purchase.products', $purchase->id) }}">#{{ $purchase->id }}</a></td><td>{{ $purchase->point_of_sale_id ?? '—' }}</td><td>{{ $purchase->grand_total }} {{ $purchase->currency_code ?? '' }}</td><td>{{ $purchase->due_amount ?? __('Unknown balance') }}</td><td>{{ $purchase->due_date ?? '—' }}</td><td>{{ __($purchase->receipt_status) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        {{ $purchases->links() }}
    </div>
</x-backend.card>
@endif
@endsection
