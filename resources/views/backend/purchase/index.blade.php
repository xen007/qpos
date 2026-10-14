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

    <x-backend.card><form id="purchase-filters" class="grid gap-3 md:grid-cols-3">
    <x-backend.select name="supplier_id" :label="__('Supplier')" :options="[''=>__('All')]+App\Models\Supplier::orderBy('name')->pluck('name','id')->all()"/>
    <x-backend.select name="category_id" :label="__('Category')" :options="[''=>__('All')]+App\Models\Category::orderBy('name')->pluck('name','id')->all()"/>
    <x-backend.select name="receipt_status" :label="__('Status')" :options="[''=>__('All'),'pending'=>__('Pending'),'partial'=>__('Partial'),'received'=>__('Received'),'cancelled'=>__('Cancelled'),'unknown'=>__('Unknown')]"/>
    <x-backend.input name="date_from" :label="__('From')" type="date"/><x-backend.input name="date_to" :label="__('To')" type="date"/>
    <button type="reset" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('administration.reset_filters') }}</button>
    </form></x-backend.card>
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">{{ __('Line') }}</th>
                        <th class="px-3 py-3">{{ __('Supplier') }}</th>
                        <th class="px-3 py-3">{{ __('ID') }}</th>
                        <th class="px-3 py-3">{{ __('Total') }}</th>
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
                    'edit' => __('Amend before receipt'),
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

            const table=$('#datatables').DataTable({
                processing: true,
                serverSide: true,
                ordering: true,
                ajax: {
                    url: config.ajax,
                    data:d=>{for(const [k,v] of new FormData(document.getElementById("purchase-filters")))d[k]=v;}
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
                        name: 'grand_total'
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

                            if (config.can.edit && row.can_amend) {
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
            $('#purchase-filters').on('change','select,input',()=>table.ajax.reload());
            $('#purchase-filters').on('submit',e=>{e.preventDefault();table.ajax.reload();});
            $('#purchase-filters').on('reset',()=>setTimeout(()=>table.ajax.reload(),0));
        });
    </script>
@endpush
