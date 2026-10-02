@extends('backend.master-tailwind')
@section('title', __('Catalogue conversion'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Legacy records are preserved. Configure unknown units and fractional quantity rules before using the new catalogue operations.') }}</p>
    @if ($run)
        <p>{{ __('Status') }} : {{ $run->status }}</p>
        <p>{{ __('Date') }} : {{ $run->completed_at }}</p>
    @endif
    <div class="overflow-x-auto mt-5">
        <table class="w-full text-left text-sm">
            <thead><tr><th class="p-3">{{ __('Product') }}</th><th class="p-3">{{ __('Issue') }}</th><th class="p-3">{{ __('Actions') }}</th></tr></thead>
            <tbody>
            @foreach ($issues as $issue)
                <tr class="border-t border-qpos-line">
                    <td class="p-3">{{ $issue->product_name ?? '—' }}</td>
                    <td class="p-3">{{ __('conversion.'.$issue->kind) }}</td>
                    <td class="p-3">@can('product_update')<a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.products.edit', $issue->source_id) }}">{{ __('Configure product') }}</a>@endcan</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $issues->links('pagination::tailwind') }}
</x-backend.card>
@endsection
