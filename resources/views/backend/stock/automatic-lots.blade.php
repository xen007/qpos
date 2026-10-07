@extends('backend.layouts.master')
@extends('backend.master-tailwind')
@section('title', __('Lots automatiques'))
@section('content')
<div class="container-fluid py-3">
    <h1>{{ __('Lots automatiques') }}</h1>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-5"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Produit, SKU ou lot') }}"></div>
        <div class="col-md-2"><select name="cost_unknown" class="form-select"><option value="">{{ __('Tous les coûts') }}</option><option value="1" @selected(request('cost_unknown')==='1')>{{ __('Coût inconnu') }}</option><option value="0" @selected(request('cost_unknown')==='0')>{{ __('Coût connu') }}</option></select></div>
        <div class="col-md-2"><select name="estimated_expiry" class="form-select"><option value="">{{ __('Toutes péremptions') }}</option><option value="1" @selected(request('estimated_expiry')==='1')>{{ __('Estimée') }}</option><option value="0" @selected(request('estimated_expiry')==='0')>{{ __('Non estimée') }}</option></select></div>
        <div class="col-md-2"><button class="btn btn-primary">{{ __('Filtrer') }}</button></div>
    </form>
    @foreach($batches as $batch)
        <section class="card mb-3"><div class="card-body">
            <div class="d-flex gap-2 align-items-center mb-2"><strong>{{ $batch->product?->name }}</strong><span class="badge bg-warning text-dark">AUTO</span><span>{{ $batch->batch_number }}</span><span>{{ __('Disponible') }}: {{ $batch->on_hand ?? '0.000000' }}</span>
                @if($batch->cost_unknown)<span class="badge bg-secondary">{{ __('Coût inconnu') }}</span>@endif
                @if($batch->estimated_expiry)<span class="badge bg-info">{{ __('Péremption estimée') }}</span>@endif
            </div>
            <form method="post" action="{{ route('backend.admin.stock.automatic-lots.update', $batch) }}" class="row g-2">@csrf @method('PUT')
                <input type="hidden" name="operation_key" value="{{ Illuminate\Support\Str::uuid() }}">
                <div class="col-md-2"><label>{{ __('Lot') }}</label><input name="batch_number" class="form-control" value="{{ $batch->batch_number }}" required></div>
                <div class="col-md-2"><label>{{ __('Coût unitaire') }}</label><input name="unit_cost" class="form-control" value="{{ $batch->unit_cost ?? '0' }}" required></div>
                <div class="col-md-2"><label>{{ __('Devise') }}</label><select name="currency_code" class="form-select"><option value="">—</option>@foreach(['XAF','BDT'] as $currency)<option value="{{ $currency }}" @selected($batch->currency_code===$currency)>{{ $currency }}</option>@endforeach</select></div>
                <div class="col-md-2"><label>{{ __('Péremption') }}</label><select name="expiry_status" class="form-select">@foreach(['unknown','dated','not_applicable'] as $status)<option value="{{ $status }}" @selected($batch->expiry_status===$status)>{{ __($status) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label>{{ __('Date') }}</label><input type="date" name="expires_on" class="form-control" value="{{ $batch->expires_on?->format('Y-m-d') }}"></div>
                <div class="col-md-2"><label>{{ __('Coût inconnu') }}</label><select name="cost_unknown" class="form-select"><option value="1" @selected($batch->cost_unknown)>Oui</option><option value="0" @selected(!$batch->cost_unknown)>Non</option></select></div>
                <div class="col-md-2"><label>{{ __('Date estimée') }}</label><select name="estimated_expiry" class="form-select"><option value="1" @selected($batch->estimated_expiry)>Oui</option><option value="0" @selected(!$batch->estimated_expiry)>Non</option></select></div>
                <div class="col-md-7"><label>{{ __('Motif de correction') }}</label><input name="reason" class="form-control" required maxlength="5000"></div>
                <div class="col-md-3 align-self-end"><button class="btn btn-outline-primary">{{ __('Enregistrer avec historique') }}</button></div>
            </form>
        </div></section>
    @endforeach
    {{ $batches->links() }}
</div>
@endsection
