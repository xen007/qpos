@props(['caption' => null])
<div class="qpos-table-scroll" role="region" aria-label="{{ $caption ?: __('Table') }}" tabindex="0">
    <table {{ $attributes->class(['qpos-table']) }}>
        @if ($caption) <caption class="sr-only">{{ $caption }}</caption> @endif
        {{ $slot }}
    </table>
</div>
