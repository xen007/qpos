{{--
    Zone de texte (skin Tailwind), meme comportement que x-backend.input.
--}}
@props(['name', 'label' => null, 'value' => null, 'rows' => 3, 'required' => false, 'placeholder' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-qpos-ink">
            {{ $label }}
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge([
            'class' => 'mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none',
        ]) }}>{{ old($name, $value) }}</textarea>

    @if (isset($errors) && $errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>
