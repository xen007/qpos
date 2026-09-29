@extends('backend.master-tailwind')

@section('title', __('Categories'))

@section('page-actions')
    @can('category_create')
        <a href="{{ route('backend.admin.categories.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <i class="fas fa-plus-circle" aria-hidden="true"></i>
            {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('content')
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3"></th>
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('Status') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-categories-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.categories.index'),
                'csrf' => csrf_token(),
                'routes' => [
                    'edit' => route('backend.admin.categories.edit', ':id'),
                    'destroy' => route('backend.admin.categories.destroy', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'active' => __('Active'),
                    'inactive' => __('Inactive'),
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        ) !!}
    </script>
@endsection

@include('backend.layouts.tailwind.plugins.datatables')

@push('script')
    @vite('resources/js/table-actions.js')

    <script type="text/javascript">
        $(function() {
            const config = JSON.parse(document.getElementById('qpos-categories-table').textContent);
            const withId = (template, id) => template.replace(':id', id);

            $('#datatables').DataTable({
                processing: true,
                serverSide: true,
                ordering: true,
                order: [
                    [1, 'asc']
                ],
                ajax: {
                    url: config.ajax
                },

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'image',
                        name: 'image'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        render: (isActive) => window.qposTableActions.statusBadge(isActive, config.labels),
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: (value, type, row) => window.qposTableActions.inline({
                            editUrl: withId(config.routes.edit, row.id),
                            destroyUrl: withId(config.routes.destroy, row.id),
                            csrf: config.csrf,
                            labels: config.labels,
                        }),
                    },
                ]
            });
        });
    </script>
@endpush
