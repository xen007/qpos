@extends('backend.master-tailwind')

@section('title', __('Products'))

@section('page-actions')
    @can('product_create')
        <a href="{{ route('backend.admin.products.create') }}"
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
                        <th class="px-3 py-3">{{ __('Price') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Stock') }}</th>
                        <th class="px-3 py-3">{{ __('Created') }}</th>
                        <th class="px-3 py-3">{{ __('Status') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-products-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.products.index'),
                'csrf' => csrf_token(),
                'fallbackImage' => asset('assets/images/no-image.png'),
                'routes' => [
                    'edit' => route('backend.admin.products.edit', ':id'),
                    'destroy' => route('backend.admin.products.destroy', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Delete'),
                    'purchase' => __('Purchase'),
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
            const config = JSON.parse(document.getElementById('qpos-products-table').textContent);
            const withId = (template, id) => template.replace(':id', id);
            const escapeHtml = window.qposTableActions.escapeHtml;

            $('#datatables').DataTable({
                processing: true,
                serverSide: true,
                ordering: true,
                ajax: {
                    url: config.ajax
                },

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'thumb_url',
                        name: 'thumb_url',
                        orderable: false,
                        searchable: false,
                        render: (url, type, row) =>
                            `<img src="${url}" alt="${escapeHtml(row.name)}" loading="lazy"
                                class="h-16 w-12 rounded-lg object-cover"
                                onerror="this.onerror=null; this.src='${config.fallbackImage}';" height="80" width="60">`,
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'price_value',
                        name: 'price_value',
                        render: (value, type, row) => Number(row.price_original) > Number(row.price_value) ?
                            `${row.price_value}<br><del class="text-qpos-muted">${row.price_original}</del>` :
                            row.price_value,
                    },
                    {
                        data: 'quantity_value',
                        name: 'quantity_value',
                        render: (value, type, row) => `${row.quantity_value ?? ''} ${escapeHtml(row.unit_short ?? '')}`,
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
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
                                    url: withId(config.routes.destroy, row.id),
                                    method: 'DELETE',
                                    label: config.labels.delete,
                                    icon: 'fas fa-trash',
                                    confirm: config.labels.confirm,
                                },
                                {
                                    type: 'link',
                                    url: row.purchase_url,
                                    label: config.labels.purchase,
                                    icon: 'fas fa-cart-plus',
                                },
                            ],
                        }),
                    },
                ]
            });
        });
    </script>
@endpush
