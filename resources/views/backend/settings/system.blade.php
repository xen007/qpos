@extends('backend.master-tailwind')
@section('title', __('System settings'))
@section('content')
<x-backend.card>
    <form method="post" action="{{ route('backend.admin.settings.system.update') }}" class="space-y-5">@csrf
        <label class="flex items-center gap-3"><input type="hidden" name="multi_shop_enabled" value="0"><input type="checkbox" name="multi_shop_enabled" value="1" @checked(old('multi_shop_enabled',$multiShopEnabled))><span>{{ __('Enable multi-shop mode') }}</span></label>
        <p class="text-sm text-qpos-muted">{{ __('Mono mode uses MAIN. Switching to mono is refused while another shop has business records; switching back restores the other shops.') }}</p>
        <label class="block">{{ __('Estimated expiry months') }}<input class="qpos-control mt-1 w-full" type="number" min="1" max="120" name="default_expiry_months" value="{{ old('default_expiry_months',$defaultExpiryMonths) }}" required></label>
        <label class="flex items-center gap-3"><input type="hidden" name="auto_generate_lots" value="0"><input type="checkbox" name="auto_generate_lots" value="1" @checked(old('auto_generate_lots',$autoGenerateLots))><span>{{ __('Generate automatic lots for undocumented openings') }}</span></label>
        <label class="block">{{ __('Automatic lot prefix') }}<input class="qpos-control mt-1 w-full" name="default_lot_prefix" maxlength="32" value="{{ old('default_lot_prefix',$defaultLotPrefix) }}" required></label>
        <label class="flex items-center gap-3"><input type="hidden" name="allow_sale_without_lot" value="0"><input type="checkbox" name="allow_sale_without_lot" value="1" @checked(old('allow_sale_without_lot',$allowSaleWithoutLot))><span>{{ __('Allow sale without a lot when automatic lots are disabled') }}</span></label>
        <button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save settings') }}</button>
    </form>
</x-backend.card>
@endsection
