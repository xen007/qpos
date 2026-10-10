<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Yajra\DataTables\DataTables;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Trait\FileHandler;

class UserManagementController extends Controller
{
    public $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $users = User::with('roles')->latest()->get();

            return DataTables::of($users)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (avatar, etat, actions) cote page, sans markup Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_suspended', fn($data) => (bool) $data->is_suspended)
                ->addColumn('thumb_url', fn($data) => $data->pro_pic)
                ->addColumn(
                    'thumb',
                    '<img class="img-fluid" src="{{ $pro_pic }}" width="50" alt="{{ $name }}">'
                )
                ->addColumn('created', function ($data) {
                    return \Illuminate\Support\Carbon::parse($data->created_at)->translatedFormat('d M, Y');
                })
                ->addColumn(
                    'action',
                    '<div class="action-wrapper">
                    <a class="btn btn-sm bg-gradient-primary"
                        href="{{ route(\'backend.admin.user.edit\', $id) }}">
                        <i class="fas fa-edit"></i>
                        {{ __(\'Edit\') }}
                    </a>
                    <form action="{{ route(\'backend.admin.user.delete\', $id) }}" method="post"
                        onsubmit="return confirm(\'{{ __(\'Are you sure you want to delete this item?\') }}\')">
                        @csrf
                        <button type="submit" class="btn btn-sm bg-gradient-danger">
                            <i class="fas fa-trash-alt"></i>
                            {{ __(\'Delete\') }}
                        </button>
                    </form>
                    @if ($is_suspended)
                        <form action="{{ route(\'backend.admin.user.suspend\', [\'id\' => $id, \'status\' => 0]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-sm bg-gradient-success">{{ __(\'Activate\') }}</button>
                        </form>
                    @else
                        <form action="{{ route(\'backend.admin.user.suspend\', [\'id\' => $id, \'status\' => 1]) }}" method="post"
                            onsubmit="return confirm(\'{{ __(\'Are you sure you want to change the status of this user?\') }}\')">
                            @csrf
                            <button type="submit" class="btn btn-sm bg-gradient-warning">{{ __(\'Suspend\') }}</button>
                        </form>
                    @endif
                    
                </div>'
                )
                ->addColumn('suspend', function ($data) {
                    if ($data->is_suspended == 0) {
                        return '<span class="badge badge-pill badge-success">' . e(__('Active')) . '</span>';
                    } else {
                        return '<span class="badge badge-pill badge-danger">' . e(__('Suspended')) . '</span>';
                    }
                })
                ->addColumn('roles', function ($data) {
                    foreach ($data->roles as $key => $role) {
                        return $role->name;
                        if (!$key + 1 != count($data->roles)) {
                            return "<br>";
                        }
                    }
                })
                ->rawColumns(['thumb', 'action', 'suspend'])
                ->toJson();
        }

        return view('backend.users.index');
    }

    public function fetchPageData(Request $request)
    {
        abort_if(!auth()->user()->can('user_view'), 403);
        if ($request->ajax()) {
            $users = User::where('type', 'User')->latest()->paginate(10);

            return view('backend.users.user-table-data', compact('users'))->render();
        }
    }

    public function suspend($id, $status)
    {
        $user = User::findOrFail($id);
        if (demoUserCheck($user->email)) {
            return back()->with('error', __('Cannot update details of demo user'));
        }

        if ($user->is_suspended == $status) {
            return back()->with('error', __('User already suspended'));
        } else {
            $user->is_suspended = $status;
            $user->save();

            return back()->with('success', __('User suspended successfully'));
        }
    }

    public function create(StoreUserRequest $request)
    {
        if ($request->isMethod('post')) {
            $selectedRoles=\App\Support\RoleAssignment::resolve($request->user(),$request->input('roles'));
            $newUser = new User();
            $newUser->name = $request->name;
            $newUser->email = $request->email;
            $newUser->password = bcrypt($request->password);
            $newUser->username = uniqid();

            if ($request->hasFile("profile_image")) {
                $newUser->profile_image = $this->fileHandler->fileUploadAndGetPath($request->file("profile_image"), "/public/media/users");
            }
            $newUser->save();

            $newUser->syncRoles($selectedRoles->all());

            return to_route('backend.admin.users')->with('success', __('User added successfully'));
        } else {
            $roles = \App\Support\RoleAssignment::choices($request->user());
            return view('backend.users.create', compact('roles'));
        }
    }

    public function edit(UpdateUserRequest $request, $id)
    {

        $user = User::with('roles')->findOrFail($id);
        \App\Support\RoleAssignment::protectTarget($request->user(),$user);

        if ($request->isMethod('post')) {
            if (demoUserCheck($user->email)) {
                return back()->with('error', __('Cannot update details of demo user'));
            }

            $change = DB::transaction(function () use ($request, $user) {
                $adminRole = Role::where('name', 'Admin')->lockForUpdate()->first();
                $roles = \App\Support\RoleAssignment::resolve($request->user(),$request->input('roles'));

                if ($user->hasRole('Admin') && !$roles->contains('name','Admin') && $adminRole && User::role('Admin')->count() <= 1) {
                    return ['allowed' => false];
                }

                $oldImage = $user->profile_image;
                if ($request->name !== $user->name) {
                    $user->name = $request->name;
                }
                if ($request->email !== $user->email) {
                    $user->email = $request->email;
                    $user->google_id = null;
                    $user->is_google_registered = false;
                }
                if ($request->filled('password')) {
                    $user->password = bcrypt($request->input('password'));
                }
                if ($request->hasFile('profile_image')) {
                    $user->profile_image = $this->fileHandler->fileUploadAndGetPath($request->file('profile_image'), '/public/media/users');
                }
                $user->save();
                $user->syncRoles($roles->all());

                return ['allowed' => true, 'old_image' => $request->hasFile('profile_image') ? $oldImage : null];
            });

            if (!$change['allowed']) {
                return back()->with('error', __('The last administrator account must keep the administrator role.'));
            }
            if ($change['old_image']) {
                $this->fileHandler->secureUnlink($change['old_image']);
            }

            return to_route('backend.admin.users')->with('success', __('User updated successfully'));
        } else {
            if ($id == auth()->id()) {
                return to_route('backend.admin.profile');
            }

            $roles = \App\Support\RoleAssignment::choices($request->user());
            return view('backend.users.edit', compact('user', 'roles'));
        }
    }

    public function delete($id)
    {

        if ($id == auth()->id()) {
            return back()->with('error', __('You cannot delete your own account.'));
        }
        if ($id == 1) {
            return back()->with('error', __('The primary administrator account cannot be deleted.'));
        }

        $deletionBlock = DB::transaction(function () use ($id) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            $adminRole = Role::where('name', 'Admin')->lockForUpdate()->first();
            if ($user->hasRole('Admin') && $adminRole && User::role('Admin')->count() <= 1) {
                return 'last_admin';
            }
            if (
                Order::where('user_id', $user->id)->exists()
                || Purchase::where('user_id', $user->id)->exists()
                || OrderTransaction::where('user_id', $user->id)->exists()
            ) {
                return 'history';
            }
            $user->delete();
            return null;
        });

        if ($deletionBlock === 'last_admin') {
            return back()->with('error', __('The last administrator account cannot be deleted.'));
        }
        if ($deletionBlock === 'history') {
            return back()->with('error', __('Users with sales or purchase history cannot be deleted.'));
        }

        return back()->with('success', __('User deleted successfully'));
    }
}
