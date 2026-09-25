@extends('backend.master')

@section('title', __('User Management'))

@section('content')
<div class="card">
    @can('user_create')
    <div class="mt-n5 mb-3 d-flex justify-content-end">
        <a href="{{ route('backend.admin.user.create') }}" class="btn bg-gradient-primary">
            <i class="fas fa-plus-circle"></i>
            {{ __('Add New') }}
        </a>
    </div>
    @endcan
    <div class="card-body p-2 p-md-4 pt-0">
        <div class="row g-4">
            <div class="col-md-12">
                <div class="card-body table-responsive p-0" id="table_data">
                    <table id="datatables" class="table table-hover">
                        <thead>
                            <tr>
                                <th data-orderable="false">#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th data-orderable="false">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script type="text/javascript">
    $(function() {
        let table = $('#datatables').DataTable({
            processing: true,
            serverSide: true,
            ordering: true,
            order: [
                [1, 'asc']
            ],
            ajax: {
                url: "{{ route('backend.admin.users') }}"
            },

            columns: [{
                    data: 'thumb',
                    name: 'thumb',
                }, {
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
                    data: 'suspend',
                    name: 'ststus'
                },
                {
                    data: 'action',
                    name: 'action'
                },
            ]
        });
    });
</script>
@endpush
