@props(['name'])
@php
    $aliases = config('ui-icons', []);
    $iconName = $name;
    foreach (explode(' ', $name) as $part) {
        if (isset($aliases[$part])) { $iconName = $aliases[$part]; break; }
    }
    $known = array_merge(array_values($aliases), ['sun', 'moon', 'check-circle-2', 'inbox']);
    if (!in_array($iconName, $known, true)) { $iconName = 'circle-help'; }
@endphp
<svg {{ $attributes->merge(['class' => 'qpos-icon']) }} width="20" height="20" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <use href="{{ asset('icons/lucide/sprite.svg') }}#{{ $iconName }}"></use>
</svg>
