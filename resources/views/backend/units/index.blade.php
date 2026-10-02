@extends('backend.master-tailwind')

@section('title', __('Units'))

@section('page-actions')
    @can('unit_create')
        <a href="{{ route('backend.admin.units.create') }}"
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
                        <th class="px-3 py-3">{{ __('Title') }}</th>
                        <th class="px-3 py-3">{{ __('Short Name') }}</th>
                        <th data-orderable="false" class="px-3 py-3">{{ __('Status') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-units-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.units.index'),
                'csrf' => csrf_token(),
                'canEdit' => auth()->user()->can('unit_update'),
                'canDeactivate' => $catalogueUnitsReady && auth()->user()->can('unit_delete'),
                'routes' => [
                    'edit' => route('backend.admin.units.edit', ':id'),
                    'destroy' => route('backend.admin.units.destroy', ':id'),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Deactivate'),
                    'confirm' => __('Deactivate this unit? Existing references will be preserved.'),
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
            const config = JSON.parse(document.getElementById('qpos-units-table').textContent);
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
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'short_name',
                        name: 'short_name'
                    },
                    {
                        data: 'state_label',
                        name: 'is_active',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: (value, type, row) => window.qposTableActions.buttons({
                            csrf: config.csrf,
                            items: [
                                ...(config.canEdit ? [{type: 'link', url: withId(config.routes.edit, row.id), label: config.labels.edit, icon: 'fas fa-edit'}] : []),
                                ...(config.canDeactivate ? [{type: 'form', method: 'DELETE', url: withId(config.routes.destroy, row.id), label: config.labels.delete, icon: 'fas fa-ban', confirm: config.labels.confirm}] : []),
                            ],
                        }),
                    },
                ]
            });
        });
    </script>
@endpush
