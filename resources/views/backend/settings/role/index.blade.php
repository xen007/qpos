@extends('backend.master')

@section('title', __('Roles'))

@section('content')
<div class="card">
    <div class="mt-n5 mb-3 d-flex justify-content-end">
        @can('role_create')
        <button class="btn bg-gradient-primary" data-toggle="modal" data-target="#roleModal">
            <i class="fas fa-plus-circle"></i>
            {{ __('Add New') }}
        </button>
        @endcan
        <!-- Modal -->
        <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('backend.admin.roles.create') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <i class="fas fa-plus-circle"></i>
                            {{ __('Add new role') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="role-name">{{ __('Name') }}</label>
                            <input id="role-name" type="text" name="name" value="{{ old('name') }}"
                                class="form-control" placeholder="{{ __('Role Name') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-gradient-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button class="btn bg-gradient-primary">{{ __('Submit') }}</button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                        <tr>
                            <td> {{ $role->name }} </td>
                            <td>
                                <div class="text-center">
                                    <a title="{{ __('Permission Setup') }}"
                                        href="{{ route('backend.admin.roles.show', $role->id) }}" type="button"
                                        class="btn btn-dark btn-xs">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    @if ($role->name !== 'Admin')
                                    <button title="{{ __('Edit Role') }}" type="button" class="btn bg-gradient-primary btn-xs"
                                        data-toggle="modal" data-target="#editRole-{{ $role->id }}">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <form method="POST" action="{{ route('backend.admin.roles.delete', $role->id) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button title="{{ __('Delete Role') }}" type="submit" class="btn btn-danger btn-xs"
                                        onclick="return confirm(@json(__('Are you sure you want to delete this item?'))) ">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    </form>
                                    @endif
                                </div>

                                <!-- Modal -->
                                <div class="modal fade" id="editRole-{{ $role->id }}" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('backend.admin.roles.update', $role->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fs-5" id="exampleModalLabel">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    {{ __('Edit Role') }}
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label class="control-label">{{ __('Name') }}:</label>
                                                    <input type="text" name="name" value="{{ old('name', $role->name) }}"
                                                        class="form-control" placeholder="{{ __('Role Name') }}" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn bg-gradient-secondary"
                                                    data-dismiss="modal">
                                                    {{ __('Close') }}
                                                </button>
                                                <button type="submit" class="btn bg-gradient-primary">
                                                    {{ __('Save changes') }}
                                                </button>
                                            </div>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
