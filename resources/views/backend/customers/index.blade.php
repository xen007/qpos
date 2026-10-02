@extends('backend.master-tailwind')

@section('title', __('Customers'))

@section('page-actions')
    @can('customer_create')
        <a href="{{ route('backend.admin.customers.create') }}"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-plus-circle" />
            {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('content')
    @php
        // Les actions de ligne restent soumises aux memes permissions que dans
        // le controleur d'origine.
        $canEditCustomer = auth()->user()->can('customer_update');
        $canDeleteCustomer = auth()->user()->can('customer_delete');
        $canViewSales = auth()->user()->can('customer_sales');
    @endphp

    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('Phone') }}</th>
                        <th class="px-3 py-3">{{ __('Address') }}</th>
                        <th class="px-3 py-3">{{ __('Created') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-customers-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.customers.index'),
                'csrf' => csrf_token(),
                'can' => [
                    'edit' => $canEditCustomer,
                    'delete' => $canDeleteCustomer,
                    'sales' => $canViewSales,
                ],
                'routes' => [
                    'edit' => route('backend.admin.customers.edit', ':id'),
                    'destroy' => route('backend.admin.customers.destroy', ':id'),
                    'sales' => route('backend.admin.customers.orders', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'sales' => __('Sales'),
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
            const config = JSON.parse(document.getElementById('qpos-customers-table').textContent);
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
                        data: 'phone',
                        name: 'phone'
                    },
                    {
                        data: 'address',
                        name: 'address'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: (value, type, row) => {
                            const items = [];

                            if (config.can.edit) {
                                items.push({
                                    type: 'link',
                                    url: withId(config.routes.edit, row.id),
                                    label: config.labels.edit,
                                    icon: 'fas fa-edit',
                                    disabled: row.is_default,
                                });
                            }

                            if (config.can.delete) {
                                items.push({
                                    type: 'form',
                                    url: withId(config.routes.destroy, row.id),
                                    method: 'DELETE',
                                    label: config.labels.delete,
                                    icon: 'fas fa-trash',
                                    confirm: config.labels.confirm,
                                    disabled: row.is_default,
                                });
                            }

                            if (config.can.sales) {
                                items.push({
                                    type: 'link',
                                    url: withId(config.routes.sales, row.id),
                                    label: config.labels.sales,
                                    icon: 'fas fa-cart-plus',
                                });
                            }

                            return window.qposTableActions.buttons({
                                csrf: config.csrf,
                                items
                            });
                        },
                    },
                ]
            });
        });
    </script>
@endpush
