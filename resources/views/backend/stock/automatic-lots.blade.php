@extends('backend.master-tailwind')
@section('title', __('Lots automatiques'))
@section('content')
<div class="grid gap-4">
<x-backend.card>
    <form method="get" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-backend.input name="search" :restore-old="false" :label="__('Produit, SKU ou lot')" :value="request('search')" />
        <x-backend.select name="cost_unknown" :restore-old="false" :label="__('Tous les coûts')" :options="[1=>__('Coût inconnu'),0=>__('Coût connu')]" :selected="request('cost_unknown')" :placeholder="__('Tous les coûts')" />
        <x-backend.select name="estimated_expiry" :restore-old="false" :label="__('Toutes péremptions')" :options="[1=>__('Estimée'),0=>__('Non estimée')]" :selected="request('estimated_expiry')" :placeholder="__('Toutes péremptions')" />
        <div class="self-end"><x-backend.button>{{ __('Filtrer') }}</x-backend.button></div>
    </form>
</x-backend.card>
@forelse($batches as $batch)
@php($restoreBatch = (int)old('batch_id') === (int)$batch->id)
<x-backend.card :title="$batch->product?->name">
    <div class="mb-4 flex flex-wrap gap-3 items-center">
        <x-backend.badge variant="warning">AUTO</x-backend.badge><strong>{{ $batch->batch_number }}</strong>
        <span>{{ __('Disponible') }} : {{ \App\Support\SaleFormat::decimal($batch->on_hand??0) }}</span>
        @if($batch->cost_unknown)<x-backend.badge>{{ __('Coût inconnu') }}</x-backend.badge>@endif
        @if($batch->estimated_expiry)<x-backend.badge>{{ __('Péremption estimée') }}</x-backend.badge>@endif
    </div>
    <form method="post" action="{{ route('backend.admin.stock.automatic-lots.update',$batch) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @csrf @method('PUT')
        <input type="hidden" name="operation_key" value="{{ Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="batch_id" value="{{ $batch->id }}">
        <x-backend.input name="batch_number" :restore-old="$restoreBatch" :id="'batch-number-'.$batch->id" :label="__('Lot')" :value="$batch->batch_number" required />
        <x-backend.input name="unit_cost" :restore-old="$restoreBatch" :id="'batch-cost-'.$batch->id" :label="__('Coût unitaire')" :value="rtrim(rtrim($batch->unit_cost??'0.000000','0'),'.')?:'0'" required />
        <x-backend.select name="currency_code" :restore-old="$restoreBatch" :id="'batch-currency-'.$batch->id" :label="__('Devise')" :options="['XAF'=>'XAF','BDT'=>'BDT']" :selected="$batch->currency_code" placeholder="—" />
        <x-backend.select name="expiry_status" :restore-old="$restoreBatch" :id="'batch-expiry-'.$batch->id" :label="__('Péremption')" :options="['unknown'=>__('unknown'),'dated'=>__('dated'),'not_applicable'=>__('not_applicable')]" :selected="$batch->expiry_status" />
        <x-backend.input name="expires_on" :restore-old="$restoreBatch" :id="'batch-date-'.$batch->id" type="date" :label="__('Date')" :value="$batch->expires_on?->format('Y-m-d')" />
        <x-backend.select name="cost_unknown" :restore-old="$restoreBatch" :id="'batch-unknown-'.$batch->id" :label="__('Coût inconnu')" :options="[1=>__('Yes'),0=>__('No')]" :selected="(int)$batch->cost_unknown" />
        <x-backend.select name="estimated_expiry" :restore-old="$restoreBatch" :id="'batch-estimated-'.$batch->id" :label="__('Date estimée')" :options="[1=>__('Yes'),0=>__('No')]" :selected="(int)$batch->estimated_expiry" />
        <x-backend.input name="reason" :restore-old="$restoreBatch" :id="'batch-reason-'.$batch->id" :label="__('Motif de correction')" maxlength="5000" required />
        <div class="self-end"><x-backend.button>{{ __('Enregistrer avec historique') }}</x-backend.button></div>
    </form>
</x-backend.card>
@empty
<x-backend.state :title="__('reporting.empty')" />
@endforelse
{{ $batches->links() }}
</div>
@endsection
