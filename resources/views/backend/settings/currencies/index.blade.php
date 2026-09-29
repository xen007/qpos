@extends('backend.master-tailwind')

@section('title', __('Currency'))

@section('page-actions')
    @can('currency_create')
        <a href="{{ route('backend.admin.currencies.create') }}"
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
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('Code') }}</th>
                        <th class="px-3 py-3">{{ __('Symbol') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-currencies-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.currencies.index'),
                'csrf' => csrf_token(),
                'routes' => [
                    'edit' => route('backend.admin.currencies.edit', ':id'),
                    'destroy' => route('backend.admin.currencies.destroy', ':id'),
                    'setDefault' => route('backend.admin.currencies.setDefault', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'default' => __('Default Currency'),
                    'setDefault' => __('Set Default'),
                    'confirmDefault' => __('Are you sure you want to set this currency as default?'),
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
            const config = JSON.parse(document.getElementById('qpos-currencies-table').textContent);
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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'symbol',
                        name: 'symbol',
                        render: (symbol, type, row) => row.is_active ?
                            `${symbol} <span class="ml-1 inline-flex rounded-full bg-qpos-brand px-2 py-0.5 text-xs font-semibold text-white">${config.labels.default}</span>` :
                            symbol,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: (value, type, row) => window.qposTableActions.buttons({
                            csrf: config.csrf,
                            items: [{
                                    type: 'link',
                                    url: withId(config.routes.edit, row.id),
                                    label: config.labels.edit,
                                    icon: 'fas fa-edit',
                                },
                                {
                                    type: 'form',
                                    url: withId(config.routes.setDefault, row.id),
                                    label: config.labels.setDefault,
                                    icon: 'fas fa-star',
                                    confirm: config.labels.confirmDefault,
                                },
                                {
                                    type: 'form',
                                    url: withId(config.routes.destroy, row.id),
                                    method: 'DELETE',
                                    label: config.labels.delete,
                                    icon: 'fas fa-trash',
                                    confirm: config.labels.confirm,
                                },
                            ],
                        }),
                    },
                ]
            });
        });
    </script>
@endpush
