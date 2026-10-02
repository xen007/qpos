@extends('backend.master-tailwind')

@section('title', __('Brands'))

@section('page-actions')
    @can('brand_create')
        <a href="{{ route('backend.admin.brands.create') }}"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-plus-circle" />
            {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('content')
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
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

    {{-- Donnees de configuration de la table : le rendu des cellules est fait
         cote page (Tailwind), a partir des colonnes neutres du serveur. --}}
    <script type="application/json" id="qpos-brands-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.brands.index'),
                'csrf' => csrf_token(),
                'routes' => [
                    'edit' => route('backend.admin.brands.edit', ':id'),
                    'destroy' => route('backend.admin.brands.destroy', ':id'),
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
            const config = JSON.parse(document.getElementById('qpos-brands-table').textContent);
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
