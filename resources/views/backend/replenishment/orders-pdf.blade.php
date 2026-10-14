<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}h1{font-size:18px}table{width:100%;border-collapse:collapse}th,td{padding:6px;border:1px solid #999}thead{display:table-header-group}.break{page-break-before:always}</style></head><body>
@foreach($orders as $po)
<section class="{{ $loop->first?'':'break' }}"><h1>{{ config('system.site_name','QPOS') }} — {{ __('replenishment.orders') }}</h1>
<p>{{ config('system.contact_address') }} · {{ config('system.contact_phone') }}</p>
<h2>{{ $po->reference }} · {{ __('replenishment.status_'.$po->status) }}</h2>
@if($po->status==='draft')<p>{{ __('replenishment.draft_document') }}</p>@endif
<p>{{ $po->snapshot['supplier']['name']??$po->supplier->name }} · {{ $po->snapshot['supplier']['address']??$po->supplier->address }}<br>{{ $po->snapshot['shop']['name']??$po->shop->name }} · {{ $po->created_at->setTimezone('Africa/Douala')->format('d/m/Y') }} · {{ $po->expected_date }}</p>
<table><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Packaging') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Price') }} XAF</th><th>{{ __('Discount') }}</th></tr></thead><tbody>
@foreach($po->items as $l)<tr><td>{{ $l->snapshot['name']??'' }}<br>{{ $l->snapshot['supplier_reference']??'' }}</td><td>{{ $l->snapshot['unit_label']??'' }} × {{ App\Support\SaleFormat::quantityDisplay($l->snapshot['factor']??'1') }}</td><td>{{ App\Support\SaleFormat::quantityDisplay($l->quantity_ordered) }}</td><td>{{ $l->unit_price===null?'—':App\Support\SaleFormat::moneyDisplay($l->unit_price) }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($l->discount_amount) }}</td></tr>@endforeach</tbody></table>
<p>{{ __('Total') }}: {{ $po->total_amount===null?'—':App\Support\SaleFormat::moneyDisplay($po->total_amount).' XAF' }}</p><p>{{ $po->notes }}</p>
</section>@endforeach
</body></html>
