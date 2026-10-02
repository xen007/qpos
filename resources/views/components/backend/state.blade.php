@props(['title', 'description' => null, 'variant' => 'empty'])
@php
    $icons = ['empty' => 'inbox', 'loading' => 'loader-circle', 'error' => 'triangle-alert', 'success' => 'check-circle-2'];
    $tone = array_key_exists($variant, $icons) ? $variant : 'empty';
@endphp
<div {{ $attributes->class(['qpos-state', 'qpos-state-'.$tone]) }}
    @if ($tone === 'loading') role="status" aria-live="polite" aria-busy="true" @elseif ($tone === 'error') role="alert" @endif>
    <span class="qpos-state-icon"><x-backend.icon :name="$icons[$tone]" /></span>
    <h3>{{ $title }}</h3>
    @if ($description) <p>{{ $description }}</p> @endif
    @if (! $slot->isEmpty()) <div class="qpos-state-actions">{{ $slot }}</div> @endif
</div>
