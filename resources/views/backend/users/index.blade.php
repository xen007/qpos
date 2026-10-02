@extends('backend.master-tailwind')

@section('title', __('User Management'))

@section('page-actions')
    @can('user_create')
        <a href="{{ route('backend.admin.user.create') }}"
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
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('Email') }}</th>
                        <th class="px-3 py-3">{{ __('Role') }}</th>
                        <th class="px-3 py-3">{{ __('Created') }}</th>
                        <th class="px-3 py-3">{{ __('Status') }}</th>
                        <th data-orderable="false" class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>

    <script type="application/json" id="qpos-users-table">
        {!! json_encode(
            [
                'ajax' => route('backend.admin.users'),
                'csrf' => csrf_token(),
                'routes' => [
                    'edit' => route('backend.admin.user.edit', ':id'),
                    'destroy' => route('backend.admin.user.delete', ':id'),
                    'suspend' => route('backend.admin.user.suspend', ['id' => ':id', 'status' => 1]),
                    'activate' => route('backend.admin.user.suspend', ['id' => ':id', 'status' => 0]),
                ],
                'labels' => [
                    'edit' => __('Edit'),
                    'delete' => __('Delete'),
                    'suspend' => __('Suspend'),
                    'activate' => __('Activate'),
                    'active' => __('Active'),
                    'suspended' => __('Suspended'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'confirmStatus' => __('Are you sure you want to change the status of this user?'),
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
            const config = JSON.parse(document.getElementById('qpos-users-table').textContent);
            const withId = (template, id) => template.replace(':id', id);
            const statusLabels = {
                active: config.labels.active,
                inactive: config.labels.suspended
            };

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
                        data: 'thumb_url',
                        name: 'thumb_url',
                        orderable: false,
                        searchable: false,
                        render: (url, type, row) => url ?
                            `<img src="${url}" alt="${row.name}" class="h-10 w-10 rounded-full object-cover">` :
                            '<span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-qpos-page text-qpos-muted">' + window.qposTableActions.icon('fas fa-user') + '</span>',
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'roles',
                        name: 'roles'
                    },
                    {
                        data: 'created',
                        name: 'created'
                    },
                    {
                        data: 'is_suspended',
                        name: 'is_suspended',
                        render: (isSuspended) => window.qposTableActions.statusBadge(!isSuspended, statusLabels, {
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
                                    url: withId(config.routes.edit, row.id),
                                    label: config.labels.edit,
                                    icon: 'fas fa-edit',
                                },
                                {
                                    type: 'form',
                                    url: withId(config.routes.destroy, row.id),
                                    label: config.labels.delete,
                                    icon: 'fas fa-trash-alt',
                                    confirm: config.labels.confirm,
                                },
                            ];

                            items.push(row.is_suspended ? {
                                type: 'form',
                                url: withId(config.routes.activate, row.id),
                                label: config.labels.activate,
                                icon: 'fas fa-check',
                            } : {
                                type: 'form',
                                url: withId(config.routes.suspend, row.id),
                                label: config.labels.suspend,
                                icon: 'fas fa-ban',
                                confirm: config.labels.confirmStatus,
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
