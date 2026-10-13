<?php

namespace App\Http\Controllers\Backend\RolePermission;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;
use App\Support\{RoleAssignment, UserAdministration};
use App\Services\AdministrationAudit;

class RoleController extends Controller
{
    // show roles page
    public function index()
    { 
        $roles = Role::where('guard_name','web')->with('permissions')->get();
        $scope=UserAdministration::query(auth()->user())->select('users.id');
        $counts=DB::table('model_has_roles')->where('model_type',\App\Models\User::class)->whereIn('model_id',$scope)->selectRaw('role_id, COUNT(*) as total')->groupBy('role_id')->pluck('total','role_id');
        foreach($roles as $role)$role->setAttribute('scoped_users_count',$counts[$role->id]??0);
        $permissions = Permission::where('guard_name','web')->get();
        $canManage=RoleAssignment::globalManager(auth()->user());
        return view('backend.settings.role.index', compact('roles', 'permissions','canManage'));
    }

    // create new role
    public function store(Request $request)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ]);

        DB::transaction(function () use ($request) {
            UserAdministration::lock();
            $role=Role::create(['name'=>$request->name,'guard_name'=>'web']);
            app(AdministrationAudit::class)->record('role.created','role',$role->id,[],['name'=>$role->name]);
        });
            return back()->with('success', __('Role created successfully'));
    }

    // update a role
    public function update(Request $request, $id)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $role = Role::where('guard_name','web')->findOrFail($id);
        $request->validate([
            'name' => "required|string|max:255|unique:roles,name," . $id
        ]);

        if (in_array($role->name,['Admin','shop_admin'],true) && $request->name !== $role->name) {
            return back()->with('error', __('The administrator role name cannot be changed.'));
        }

        if ($role) {
            DB::transaction(function () use ($id,$request) {
                UserAdministration::lock();
                $role=Role::whereKey($id)->where('guard_name','web')->lockForUpdate()->firstOrFail();
                $before=['name'=>$role->name];
                $role->update(['name'=>$request->name]);
                app(AdministrationAudit::class)->record('role.updated','role',$role->id,$before,['name'=>$role->name]);
            });
            return back()->with('success', __('Role has been updated'));
        } else {
            return back()->with('error', __('The selected role could not be found.'));
        }
    }

    // show permissions
    public function show($id)
    {
        $role = Role::where('guard_name','web')->findOrFail($id);
        $permissions = Permission::where('guard_name','web')->get();

        $canManage=RoleAssignment::globalManager(auth()->user());
        return view('backend.settings.role.permissions', compact('permissions', 'role','canManage'));
    }

    // delete a role
    public function destroy($id)
    {
        abort_unless(RoleAssignment::globalManager(auth()->user()),403);
        $result = DB::transaction(function () use ($id) {
            UserAdministration::lock();
            $role = Role::whereKey($id)->where('guard_name','web')->lockForUpdate()->firstOrFail();
            if (in_array($role->name,['Admin','shop_admin'],true)) {
                return 'admin';
            }

            if (DB::table('model_has_roles')->where('role_id', $role->id)->exists()) {
                return 'assigned';
            }

            $before=['name'=>$role->name,'permissions'=>$role->permissions->pluck('name')->all()];
            $role->delete();
            app(AdministrationAudit::class)->record('role.deleted','role',$id,$before);
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
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        if ($role = Role::where('guard_name','web')->findOrFail($id)) {
            // admin role has everything
            if ($role->name === 'Admin') {
                return to_route('backend.admin.roles')->with('warning', __('The administrator role always has all permissions.'));
            }
            
            $validated = $request->validate([
                'permissions' => ['sometimes', 'array'],
                'permissions.*' => ['integer','distinct', \Illuminate\Validation\Rule::exists('permissions','id')->where('guard_name','web')],
            ]);
            $permissions = $validated['permissions'] ?? [];
            if($role->name==='shop_admin'){
                $selected=Permission::whereIn('id',$permissions)->pluck('name')->all();
                abort_unless(count(array_diff($selected,\App\Support\UserAccessSettings::shopAdminCapabilities()))===0,403);
            }
            DB::transaction(function () use ($id,$permissions) {
                UserAdministration::lock();
                $role=Role::whereKey($id)->where('guard_name','web')->lockForUpdate()->firstOrFail();
                $before=['permissions'=>$role->permissions->pluck('name')->sort()->values()->all()];
                $selected=Permission::whereIn('id',$permissions)->where('guard_name','web')->get();
                $role->syncPermissions($selected);
                app(AdministrationAudit::class)->record('role.permissions_updated','role',$role->id,$before,['permissions'=>$selected->pluck('name')->sort()->values()->all()]);
            });
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return back()->with('success', __('Permissions updated for :role.', ['role' => $role->name]));
        } else {
            return back()->with('error', __('The selected role could not be found.'));
        }
    }

    // show permissions according to role
    public function roleWisePermissions($id)
    {
        if ($id != '') {
            $data = Role::where('guard_name','web')->findOrFail($id);
            return response()->json($data->permissions, 200);
        }
        return response()->json('', 200);
    }
}
