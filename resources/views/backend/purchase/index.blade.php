@extends('backend.master-tailwind')

@section('title', __('Purchase'))

@section('page-actions')
    @can('purchase_create')
        <a href="{{ route('backend.admin.purchase.create') }}"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-plus-circle" />
            {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('content')
    @php
        // L'action de modification reste soumise a la permission, comme dans le
        // controleur d'origine.
        $canEditPurchase = auth()->user()->can('purchase_update');
    @endphp

    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Supplier') }}</th>
                        <th class="px-3 py-3">{{ __('ID') }}</th>
                        <th class="px-3 py-3">{{ __('Total') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Date') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-purchases-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.purchase.index'),
                'csrf' => csrf_token(),
                'can' => [
                    'edit' => $canEditPurchase,
                ],
                'routes' => [
                    'view' => route('backend.admin.purchase.products', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'view' => __('View'),
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
            const config = JSON.parse(document.getElementById('qpos-purchases-table').textContent);
            const withId = (template, id) => template.replace(':id', id);

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
                        data: 'supplier',
                        name: 'supplier'
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'total',
                        name: 'total'
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
                                    url: row.edit_url,
                                    label: config.labels.edit,
                                    icon: 'fas fa-edit',
                                });
                            }

                            items.push({
                                type: 'link',
                                url: withId(config.routes.view, row.purchase_id),
                                label: config.labels.view,
                                icon: 'fas fa-eye',
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
