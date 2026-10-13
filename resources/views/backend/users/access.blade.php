@if($access['allowed'])
<fieldset class="lg:col-span-2 rounded-xl border border-qpos-line p-4 grid gap-4">
    <legend class="px-2 font-semibold">{{ __('phase7.additional_permissions') }}</legend>
    <input type="hidden" name="access_payload" value="1">
    <p class="text-sm text-qpos-muted">{{ __('phase7.direct_notice') }}</p>
    <details><summary class="min-h-11 cursor-pointer font-medium">{{ __('phase7.role_rights') }}</summary>
        <ul class="grid gap-1 sm:grid-cols-2">@foreach($access['inherited'] as $code)<li class="text-sm break-words">{{ $code }}</li>@endforeach</ul>
    </details>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($access['permissions'] as $permission)
        <label class="flex min-h-11 gap-3 items-center text-sm break-words"><input type="checkbox" name="direct_permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name,old('direct_permissions',$access['direct'])))><span>{{ $permission->name }}</span></label>
    @endforeach
    </div>
    <p class="text-sm"><strong>{{ __('phase7.direct_rights') }} :</strong> {{ implode(', ', $access['direct']) ?: '—' }}</p>
    <x-backend.select name="fixed_point_of_sale_id" :label="__('phase7.fixed_shop')" :options="$access['shops']->pluck('name','id')->all()" :selected="$user->fixed_point_of_sale_id??null" :placeholder="__('phase7.no_fixed_shop')" />
    <p class="text-sm text-qpos-muted">{{ __('phase7.fixed_notice') }}</p>
    <x-backend.textarea name="access_reason" :label="__('phase7.access_reason')" maxlength="500" />
    @foreach(['direct_permissions','fixed_point_of_sale_id','access_reason'] as $field)@error($field)<p role="alert" class="text-qpos-danger">{{ $message }}</p>@enderror @endforeach
</fieldset>
@endif
