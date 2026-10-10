<fieldset class="grid gap-2"><legend class="font-semibold">{{ __('Role & Permissions') }}</legend>
@foreach($roles as $role)<label class="flex items-center gap-3 min-h-11"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id,$selectedRoles))>{{ $role->name }}</label>@endforeach
@error('roles')<p role="alert" class="text-qpos-danger">{{ $message }}</p>@enderror
@foreach($errors->get('roles.*') as $messages)@foreach($messages as $message)<p role="alert" class="text-qpos-danger">{{ $message }}</p>@endforeach@endforeach
</fieldset>
