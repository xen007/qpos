@props(['variant' => 'info', 'icon' => null])
@php $tone = in_array($variant, ['success', 'danger', 'warning', 'info', 'neutral'], true) ? $variant : 'info'; @endphp
<span {{ $attributes->class(['qpos-badge', 'qpos-badge-'.$tone]) }}>
    @if ($icon) <x-backend.icon :name="$icon" /> @endif
    {{ $slot }}
</span>
