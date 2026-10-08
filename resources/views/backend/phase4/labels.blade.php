@extends('backend.master-tailwind')
@section('title', __('Price labels'))
@section('content')
<x-backend.card :title="__('Price labels')"><form method="POST" action="{{ route('backend.admin.labels.pdf') }}" class="grid gap-4">@csrf @include('backend.phase4.context')
<x-backend.select name="format" :label="__('Label format')" :options="['avery-l7160'=>'Avery L7160 · A4 · 63.5 × 38.1 mm','avery-5160'=>'Avery 5160 · Letter · 66.675 × 25.4 mm','thermal-58'=>'58 × 40 mm','thermal-80'=>'80 × 50 mm']" />
<x-backend.input name="copies" :label="__('Copies per label')" type="number" min="1" max="20" value="1" required />
@foreach($units as $u)<label class="py-2"><input type="checkbox" name="unit_ids[]" value="{{ $u->id }}" @checked(in_array($u->id,old('unit_ids',[])))> {{ $u->product->name }} / {{ $u->label }} · {{ $u->sale_price_ttc }} XAF</label>@endforeach
<p>{{ __('Print at actual size, without scaling.') }}</p><button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Download PDF') }}</button></form>{{ $units->links() }}</x-backend.card>
@endsection
