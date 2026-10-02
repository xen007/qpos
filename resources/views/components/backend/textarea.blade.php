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
        @if (isset($errors) && $errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' => 'qpos-control mt-1 w-full',
        ]) }}>{{ old($name, $value) }}</textarea>

    @if (isset($errors) && $errors->has($name))
        <p id="{{ $name }}-error" class="mt-1 text-xs text-qpos-danger" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
