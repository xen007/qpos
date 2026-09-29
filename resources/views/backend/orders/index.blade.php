@extends('backend.master-tailwind')

@section('title', __('Sales'))

@section('content')
    @php
        // L'action d'encaissement reste soumise a la permission, comme dans le
        // controleur d'origine.
        $canCollect = auth()->user()->can('sale_update');
    @endphp

    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Sale ID') }}</th>
                        <th class="px-3 py-3">{{ __('Customer') }}</th>
                        <th class="px-3 py-3">{{ __('Items') }}</th>
                        <th class="px-3 py-3">{{ __('Sub Total') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Discount') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Total') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Paid') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Due') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Status') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-orders-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.orders.index'),
                'csrf' => csrf_token(),
                'can' => [
                    'collect' => $canCollect,
                ],
                'routes' => [
                    'invoice' => route('backend.admin.orders.invoice', ':id'),
                    'posInvoice' => route('backend.admin.orders.pos-invoice', ':id'),
                    'transactions' => route('backend.admin.orders.transactions', ':id'),
                    'collect' => route('backend.admin.due.collection', ':id'),
                ],
                'labels' => [
                    'invoice' => __('Invoice'),
                    'posReceipt' => __('POS Receipt'),
                    'collectDue' => __('Collect Due'),
                    'transactions' => __('Transactions'),
                    'paid' => __('Paid'),
                    'due' => __('Due'),
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
            const config = JSON.parse(document.getElementById('qpos-orders-table').textContent);
            const withId = (template, id) => template.replace(':id', id);

            $('#datatables').DataTable({
                processing: true,
                serverSide: true,
                ordering: true,
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: config.ajax
                },

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'saleId',
                        name: 'id'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'item',
                        name: 'item_quantity_sum'
                    },
                    {
                        data: 'sub_total',
                        name: 'sub_total'
                    },
                    {
                        data: 'discount',
                        name: 'discount'
                    },
                    {
                        data: 'total',
                        name: 'total'
                    },
                    {
                        data: 'paid',
                        name: 'paid'
                    },
                    {
                        data: 'due',
                        name: 'due'
                    },
                    {
                        data: 'is_paid',
                        name: 'is_paid',
                        render: (isPaid) => window.qposTableActions.statusBadge(isPaid, {
                            active: config.labels.paid,
                            inactive: config.labels.due
                        }, {
                            inactive: 'bg-red-600 text-white',
                        }),
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: (value, type, row) => {
                            const items = [{
                                    type: 'link',
                                    url: withId(config.routes.invoice, row.id),
                                    label: config.labels.invoice,
                                    icon: 'fas fa-file-invoice',
                                },
                                {
                                    type: 'link',
                                    url: withId(config.routes.posInvoice, row.id),
                                    label: config.labels.posReceipt,
                                    icon: 'fas fa-file-invoice',
                                },
                            ];

                            if (!row.is_paid && config.can.collect) {
                                items.push({
                                    type: 'link',
                                    url: withId(config.routes.collect, row.id),
                                    label: config.labels.collectDue,
                                    icon: 'fas fa-receipt',
                                });
                            }

                            items.push({
                                type: 'link',
                                url: withId(config.routes.transactions, row.id),
                                label: config.labels.transactions,
                                icon: 'fas fa-exchange-alt',
                            });

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
