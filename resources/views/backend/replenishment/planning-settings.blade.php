@extends('backend.master-tailwind')
@section('title',__('replenishment.planning_settings'))
@section('content')
<x-backend.card :title="__('replenishment.planning_settings').' · '.$shop->name">
<p class="mb-4">{{ __('replenishment.planning_help') }}</p>
<form method="post" action="{{ route('backend.admin.replenishment.settings.save') }}" class="grid gap-4 md:grid-cols-2">
@csrf
<input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}">
<input type="hidden" name="version" value="{{ $settings['version'] }}">
<x-backend.select name="schedule" :label="__('replenishment.schedule')" :options="['on_demand'=>__('replenishment.on_demand'),'daily'=>__('replenishment.daily_schedule'),'weekly'=>__('replenishment.weekly_schedule')]" :selected="old('schedule',$settings['schedule'])"/>
<x-backend.select name="responsible_user_id" :label="__('replenishment.responsible')" :options="[''=>'—']+$responsibles" :selected="old('responsible_user_id',$settings['responsible_user_id'])"/>
<x-backend.select name="window_days" :label="__('replenishment.window')" :options="[30=>'30',60=>'60',90=>'90']" :selected="old('window_days',$settings['window_days'])"/>
@foreach(['review_days'=>[0,365],'safety_days'=>[0,365],'minimum_history_days'=>[7,90]] as $field=>$bounds)
<x-backend.input :name="$field" :label="__('replenishment.'.$field)" type="number" :min="$bounds[0]" :max="$bounds[1]" step="1" :value="old($field,$settings[$field])" required/>
@endforeach
<x-backend.input name="daily_time" :label="__('replenishment.daily_time')" type="time" :value="old('daily_time',$settings['daily_time'])" required/>
<x-backend.select name="weekly_day" :label="__('replenishment.weekly_day')" :options="collect(range(1,7))->mapWithKeys(fn($d)=>[$d=>__('replenishment.weekday_'.$d)])->all()" :selected="old('weekly_day',$settings['weekly_day'])"/>
<x-backend.input name="weekly_time" :label="__('replenishment.weekly_time')" type="time" :value="old('weekly_time',$settings['weekly_time'])" required/>
<div><input type="hidden" name="critical_immediate" value="0"><label class="flex items-center gap-3 min-h-11">
<input type="checkbox" name="critical_immediate" value="1" @checked(old('critical_immediate',$settings['critical_immediate']))>{{ __('replenishment.critical_immediate') }}</label></div>
<label class="md:col-span-2">{{ __('replenishment.recipients') }}
<select name="recipient_ids[]" multiple class="qpos-control w-full min-h-32" id="planning-recipients">
@foreach($recipients as $id=>$name)<option value="{{ $id }}" @selected(in_array($id,array_map('intval',old('recipient_ids',$settings['recipient_ids']))))>{{ $name }}</option>@endforeach
</select>
</label>
<div class="flex flex-wrap gap-3 md:col-span-2">
<button type="button" data-recipient-select="1" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('replenishment.select_page') }}</button>
<button type="button" data-recipient-select="0" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('replenishment.clear_page') }}</button>
</div>
<x-backend.input name="reason" :label="__('Reason')" maxlength="500" required/>
<div class="md:col-span-2"><button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button></div>
</form>
</x-backend.card>
@endsection
@push('script')
<script>
document.querySelectorAll('[data-recipient-select]').forEach(button=>button.addEventListener('click',()=>{
 Array.from(document.getElementById('planning-recipients').options).forEach(option=>{option.selected=button.dataset.recipientSelect==='1';});
}));
</script>
@endpush
