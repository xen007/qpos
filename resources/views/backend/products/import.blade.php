@extends('backend.master-tailwind')
@section('title', __('Product Import'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Preview writes no business data. One error blocks the entire import. Existing SKUs are rejected.') }}</p>
    <form method="post" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="operation_key" value="{{ $operationKey }}">
        <input type="hidden" name="preview_hash" value="{{ $previewHash }}">
        @if($selectedPointOfSale)<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale->id }}">@endif
        <div class="grid gap-4 lg:grid-cols-2">
            <label class="block">{{ __('CSV file (UTF-8)') }}<input class="qpos-control mt-1 w-full" type="file" name="file" accept=".csv,.txt" required></label>
            <label class="block">{{ __('Import mode') }}<select name="import_mode" class="qpos-control mt-1 w-full">
                <option value="catalogue" @selected(request('import_mode','catalogue')==='catalogue')>{{ __('Catalogue only (zero quantity)') }}</option>
                @can('purchase_receive')<option value="receipt" @selected(request('import_mode')==='receipt')>{{ __('Documented receipt (XAF)') }}</option>@endcan
            </select></label>
            <label class="block">{{ __('Supplier for receipt') }}<select name="supplier_id" class="qpos-control mt-1 w-full">
                <option value="">{{ __('Select supplier') }}</option>
                @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier_id')===(string)$supplier->id)>{{ $supplier->name }}</option>@endforeach
            </select></label>
            <label class="block">{{ __('Receipt evidence') }}<textarea name="evidence" maxlength="5000" class="qpos-control mt-1 w-full">{{ request('evidence') }}</textarea></label>
        </div>
        <p class="text-sm text-qpos-muted">{{ __('Re-select the same CSV after preview to apply or download errors. Stock quantities use the product base unit; costs use XAF.') }}</p>
        <div class="flex flex-wrap gap-3">
            <button class="qpos-button qpos-button-md qpos-button-primary" name="action" value="preview">{{ __('Preview (dry-run)') }}</button>
            @if($report && $report['valid'])<button class="qpos-button qpos-button-md qpos-button-primary" name="action" value="apply">{{ __('Apply entire file') }}</button>@endif
            @if($report && !$report['valid'])<button class="qpos-button qpos-button-md qpos-button-secondary" name="action" value="errors">{{ __('Download errors CSV') }}</button>@endif
            <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.products.import',['download-demo'=>1]) }}">{{ __('Download sample') }}</a>
        </div>
    </form>
    @if($report)
        <p class="my-4 font-semibold">{{ $report['valid'] ? __('Ready to apply') : __('Import blocked') }} · {{ $report['line_count'] }} {{ __('rows') }}</p>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>{{ __('Line') }}</th><th>SKU</th><th>{{ __('Field') }}</th><th>{{ __('Error') }}</th></tr></thead><tbody>
            @foreach($report['errors'] as $error)<tr><td class="p-2">{{ $error['line'] }}</td><td class="p-2">{{ $error['sku'] }}</td><td class="p-2">{{ $error['field'] }}</td><td class="p-2">{{ $error['message'] }}</td></tr>@endforeach
        </tbody></table></div>
        @if($report['valid'])<div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead><tr><th>SKU</th><th>{{ __('Product') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Unit') }}</th><th>{{ __('Price incl. tax') }}</th><th>{{ __('Cost (XAF)') }}</th><th>{{ __('Expiry') }}</th></tr></thead><tbody>
            @foreach($report['rows'] as $row)<tr><td class="p-2">{{ $row['values']['sku'] }}</td><td class="p-2">{{ $row['values']['name'] }}</td><td class="p-2">{{ $row['values']['quantity'] }}</td><td class="p-2">{{ $row['values']['unit'] }}</td><td class="p-2">{{ $row['values']['price'] }}</td><td class="p-2">{{ $row['values']['purchase_price'] }}</td><td class="p-2">{{ __($row['values']['expiry_status']) }} {{ $row['values']['expire_date'] }}</td></tr>@endforeach
        </tbody></table></div>@endif
    @endif
</x-backend.card>
@endsection
