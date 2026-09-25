<?php

namespace App\Http\Controllers\Backend\RolePermission;

use app;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    // show roles page
    public function index()
    { 
       abort_if(!auth()->user()->can('role_view'), 403);
        $roles = Role::all();
        $permissions = Permission::all();
        return view('backend.settings.role.index', compact('roles', 'permissions'));
    }

    // create new role
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('role_create'), 403);
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ]);

        if (Role::create($request->only('name'))) {
            return back()->with('success', __('Role created successfully'));
        } else {
            return back()->with('error', __('Something went wrong. Please try again.'));
        }
    }

    // update a role
    public function update(Request $request, $id)
    {
        abort_if(!auth()->user()->can('role_update'), 403);
        $role = Role::findOrFail($id);
        $request->validate([
            'name' => "required|string|max:255|unique:roles,name," . $id
        ]);

        if ($role->name === 'Admin' && $request->name !== 'Admin') {
            return back()->with('error', __('The administrator role name cannot be changed.'));
        }

        if ($role) {
            $role->update([
                'name' => $request->name
            ]);
            return back()->with('success', __('Role has been updated'));
        } else {
            return back()->with('error', __('The selected role could not be found.'));
        }
    }

    // show permissions
    public function show($id)
    {
        abort_if(!auth()->user()->can('role_view'), 403);
        $role = Role::findOrFail($id);
        $permissions = Permission::all();

        return view('backend.settings.role.permissions', compact('permissions', 'role'));
    }

    // delete a role
    public function destroy($id)
    {
         abort_if(!auth()->user()->can('role_delete'), 403);
        $result = DB::transaction(function () use ($id) {
            $role = Role::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($role->name === 'Admin') {
                return 'admin';
            }

            if (DB::table('model_has_roles')->where('role_id', $role->id)->exists()) {
                return 'assigned';
            }

            $role->delete();
            return 'deleted';
        });

        if ($result === 'admin') {
            return back()->with('error', __('The administrator role cannot be deleted.'));
        }
        if ($result === 'assigned') {
            return back()->with('error', __('Roles assigned to users cannot be deleted.'));
        }
        return back()->with('success', __('Role deleted successfully'));
    }

    // update permissions of a role
    public function updatePermission(Request $request, $id)
    {
        abort_if(!auth()->user()->can('role_update'), 403);
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        if ($role = Role::findOrFail($id)) {
            // admin role has everything
            if ($role->name === 'Admin') {
                $role->syncPermissions(Permission::all());
                return to_route('backend.admin.roles')->with('warning', __('The administrator role always has all permissions.'));
            }
            
            $validated = $request->validate([
                'permissions' => ['sometimes', 'array'],
                'permissions.*' => ['integer', 'exists:permissions,id'],
            ]);
            $permissions = $validated['permissions'] ?? [];
            $role->syncPermissions($permissions);
            return back()->with('success', __('Permissions updated for :role.', ['role' => $role->name]));
        } else {
            return back()->with('error', __('The selected role could not be found.'));
        }
    }

    // show permissions according to role
    public function roleWisePermissions($id)
    {
        abort_if(!auth()->user()->can('role_view'), 403);
        if ($id != '') {
            $data = Role::findOrFail($id);
            return response()->json($data->permissions, 200);
        }
        return response()->json('', 200);
    }
}
