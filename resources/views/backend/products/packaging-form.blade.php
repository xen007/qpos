@php
    $formKey = $packaging ? (string) $packaging->id : 'new';
    $retain = (string) old('_packaging_id') === $formKey;
    $value = fn ($field, $fallback) => $retain ? old($field, $fallback) : $fallback;
    $availableUnits = $units->filter(fn ($unit) => $unit->is_active || $unit->id === $packaging?->unit_id || $unit->id === $product->unit_id);
@endphp
<form method="post" action="{{ $packaging ? route('backend.admin.products.units.update', [$product, $packaging]) : route('backend.admin.products.units.store', $product) }}">
    @csrf
    @if ($packaging) @method('PUT') @endif
    <input type="hidden" name="_packaging_id" value="{{ $formKey }}">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="unit-{{ $formKey }}" class="text-sm">{{ __('Unit') }}</label>
            <select id="unit-{{ $formKey }}" name="unit_id" class="qpos-control mt-1 w-full" required>
                @foreach ($availableUnits as $unit)
                    <option value="{{ $unit->id }}" @selected((string) $value('unit_id', $packaging?->unit_id ?? $product->unit_id) === (string) $unit->id)>{{ $unit->title }} ({{ $unit->short_name }})</option>
                @endforeach
            </select>
        </div>
        @foreach (['code' => __('Packaging code'), 'label' => __('Label'), 'factor' => __('Base unit factor')] as $field => $label)
            <div>
                <label for="{{ $field }}-{{ $formKey }}" class="text-sm">{{ $label }}</label>
                <input id="{{ $field }}-{{ $formKey }}" name="{{ $field }}" type="text" class="qpos-control mt-1 w-full"
                    value="{{ $value($field, $packaging?->{$field} ?? ($field === 'factor' ? '1' : '')) }}"
                    maxlength="{{ $field === 'code' ? 64 : ($field === 'factor' ? 32 : 255) }}"
                    @if ($field === 'factor') inputmode="decimal" @endif required>
            </div>
        @endforeach
        <div>
            <label for="active-{{ $formKey }}" class="text-sm">{{ __('Status') }}</label>
            <select id="active-{{ $formKey }}" class="qpos-control mt-1 w-full" name="is_active" required>
                <option value="1" @selected((string) $value('is_active', (int) ($packaging?->is_active ?? true)) === '1')>{{ __('Active') }}</option>
                <option value="0" @selected((string) $value('is_active', (int) ($packaging?->is_active ?? true)) === '0')>{{ __('Inactive') }}</option>
            </select>
        </div>
    </div>
    <button type="submit" class="qpos-button qpos-button-md qpos-button-primary mt-4" @disabled($product->allows_fractional === null || !$product->unit_id)>{{ __('Save') }}</button>
</form>