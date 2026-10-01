@props(['name', 'label', 'type' => 'text', 'autocomplete' => null, 'minlength' => null])
<div class="qpos-auth-field">
    <label for="{{ $name }}">{{ $label }}</label>
    <div class="qpos-auth-input-wrap">
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" required
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($minlength) minlength="{{ $minlength }}" @endif
            @if ($type !== 'password') value="{{ old($name) }}" @endif
            @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
        @if ($type === 'password')
            <button type="button" class="qpos-password-toggle" data-password-toggle="{{ $name }}"
                aria-controls="{{ $name }}" aria-pressed="false" aria-label="{{ __('Show password') }}"
                data-show-label="{{ __('Show password') }}" data-hide-label="{{ __('Hide password') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        @endif
    </div>
    @error($name) <p id="{{ $name }}-error" class="qpos-field-error">{{ $message }}</p> @enderror
</div>
