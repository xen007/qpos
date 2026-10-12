<?php

namespace App\Http\Controllers\Backend\RolePermission;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use App\Support\{RoleAssignment, UserAdministration};
use App\Services\AdministrationAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    // show permission page
    public function index()
    {
        $permissions = Permission::all();

        return view('backend.settings.permission.index', compact('permissions'));
    }

    // create new permission
    public function store(Request $request)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $request->validate([
            'name' => ['required','string','max:100','regex:/\A[a-z][a-z0-9_]*\z/'],
            'type' => ['required','in:1,2'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                UserAdministration::lock();
                $names=(string)$request->type==='1'?[$request->name]:array_map(fn($action)=>$request->name.'_'.$action,['view','create','update','delete']);
                if (Permission::whereIn('name',$names)->where('guard_name','web')->exists()) throw ValidationException::withMessages(['name'=>__('validation.unique',['attribute'=>__('Name')])]);
                foreach ($names as $name) {
                    $permission=Permission::create(['name'=>$name,'guard_name'=>'web']);
                    app(AdministrationAudit::class)->record('permission.created','permission',$permission->id,[],['name'=>$name]);
                }
            });
            return back()->with('success', __('Permission added'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', __('Something went wrong. Please try again.'));
        }
    }

    // update a permission
    public function update(Request $request, $id)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $request->validate([
            'name' => ['required','string','max:100','regex:/\A[a-z][a-z0-9_]*\z/', \Illuminate\Validation\Rule::unique('permissions','name')->ignore($id)],
        ]);

        if ($data = Permission::findOrFail($id)) {
            DB::transaction(function () use ($id,$request) {
                UserAdministration::lock();
                $data=Permission::whereKey($id)->where('guard_name','web')->lockForUpdate()->firstOrFail();
                if($data->name!==$request->name) $this->assertUnused($data);
                $before=['name'=>$data->name];
                $data->update(['name'=>$request->name]);
                app(AdministrationAudit::class)->record('permission.updated','permission',$id,$before,['name'=>$data->name]);
            });
            return back()->with('success', __('Permission has been updated'));
        } else {
            return back()->with('error', __('The selected permission could not be found.'));
        }
    }

    // delete a permission
    public function destroy($id)
    {
        abort_unless(RoleAssignment::globalManager(auth()->user()),403);
        DB::transaction(function () use ($id) {
            UserAdministration::lock();
            $data=Permission::whereKey($id)->where('guard_name','web')->lockForUpdate()->firstOrFail();
            $this->assertUnused($data);
            $before=['name'=>$data->name];$data->delete();
            app(AdministrationAudit::class)->record('permission.deleted','permission',$id,$before);
        });

        return back()->with('success', __('Permission deleted successfully'));
    }

    private function assertUnused(Permission $permission): void
    {
        $referenced=DB::table('role_has_permissions')->where('permission_id',$permission->id)->exists()
            || DB::table('model_has_permissions')->where('permission_id',$permission->id)->exists();
        foreach (app('router')->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware,'permission:')) {
                    $names=explode('|',explode(',',substr($middleware,11))[0]);
                    if(in_array($permission->name,$names,true)) $referenced=true;
                }
            }
        }
        if($referenced) throw ValidationException::withMessages(['name'=>__('administration.permission_in_use')]);
    }
}
