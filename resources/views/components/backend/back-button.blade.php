@props(['href', 'label' => null])
<x-backend.button :href="$href" variant="secondary" icon="arrow-left" size="sm" {{ $attributes }}>
    {{ $label ?: __('Back') }}
</x-backend.button>
