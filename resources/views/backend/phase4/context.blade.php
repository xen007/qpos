<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale?->id }}">
<input type="hidden" name="operation_key" value="{{ old('operation_key', (string) Illuminate\Support\Str::uuid()) }}">
