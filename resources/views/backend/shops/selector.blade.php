<div class="flex flex-wrap items-center gap-3">
    @if ($availablePointsOfSale->isNotEmpty() && ($multipleActivePointsOfSale ?? false))
        <form method="post" action="{{ route('backend.admin.shops.select') }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            @csrf
            <label for="qpos-active-store" class="text-sm font-medium">{{ __('Active store') }}</label>
            <select id="qpos-active-store" name="point_of_sale_id" class="qpos-control min-w-0 flex-1 sm:max-w-xs" required>
                <option value="">{{ __('Select store') }}</option>
                @foreach ($availablePointsOfSale as $choice)
                    <option value="{{ $choice->id }}" @selected($selectedPointOfSale?->id === $choice->id)>{{ $choice->name }}</option>
                @endforeach
            </select>
            <button class="qpos-button qpos-button-sm qpos-button-secondary" type="submit">{{ __('Apply') }}</button>
        </form>
    @elseif ($availablePointsOfSale->isEmpty())
        <p class="text-sm text-qpos-muted">{{ __('No active store is assigned to your account.') }}</p>
    @else
        <span class="text-sm font-medium">{{ $selectedPointOfSale?->name }}</span>
    @endif
    @can('viewAny', \App\Models\PointOfSale::class)
        <a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.shops.index') }}">{{ __('Manage stores') }}</a>
    @endcan
</div>
