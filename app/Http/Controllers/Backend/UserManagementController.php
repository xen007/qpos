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
use App\Support\{RoleAssignment, UserAdministration};
use App\Services\AdministrationAudit;

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
            $request->validate([
                'length'=>'nullable|integer|min:1|max:100', 'start'=>'nullable|integer|min:0',
                'search.value'=>'nullable|string|max:200',
                'columns.*.name'=>'required|in:thumb_url,name,email,roles,created,is_suspended,action',
            ]);
            $users = UserAdministration::query($request->user())->with('roles');

            return DataTables::of($users)
                ->filterColumn('roles', fn($q,$term)=>$q->whereHas('roles',fn($role)=>$role->where('name','like','%'.$term.'%')))
                ->filterColumn('created', fn($q,$term)=>$q->where('users.created_at','like','%'.$term.'%'))
                ->orderColumn('created','users.created_at $1')
                ->orderColumn('roles', function ($q,$direction) {
                    $q->orderByRaw('(SELECT MIN(roles.name) FROM roles JOIN model_has_roles ON model_has_roles.role_id=roles.id WHERE model_has_roles.model_id=users.id AND model_has_roles.model_type=?) '.($direction==='desc'?'DESC':'ASC'),[User::class]);
                })
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (avatar, etat, actions) cote page, sans markup Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_suspended', fn($data) => (bool) $data->is_suspended)
                ->addColumn('thumb_url', fn($data) => $data->pro_pic)
                ->addColumn('created', function ($data) {
                    return \Illuminate\Support\Carbon::parse($data->created_at)->translatedFormat('d M, Y');
                })
                ->addColumn('action', fn()=>null)
                ->addColumn('capabilities', function ($target) use ($request) {
                    $actor=$request->user();
                    try { RoleAssignment::protectTarget($actor,$target); $allowed=true; }
                    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $allowed=false; }
                    return ['edit'=>$allowed && $actor->can('user_update'),
                        'delete'=>$allowed && $actor->can('user_delete') && $target->id!==$actor->id && $target->id!==1,
                        'suspend'=>$allowed && $actor->can('user_suspend')];
                })
                ->addColumn('roles', function ($data) {
                    return $data->roles->pluck('name')->join(', ');
                })
                ->toJson();
        }

        return view('backend.users.index');
    }

    public function fetchPageData(Request $request)
    {
        abort_if(!auth()->user()->can('user_view'), 403);
        if ($request->ajax()) {
            $users = UserAdministration::query($request->user())->where('type', 'User')->latest()->paginate(10);

            return view('backend.users.user-table-data', compact('users'))->render();
        }
    }

    public function suspend($id, $status)
    {
        abort_unless(in_array((string)$status,['0','1'],true),422);
        $user = User::findOrFail($id);
        RoleAssignment::protectTarget(auth()->user(),$user);
        if (demoUserCheck($user->email)) {
            return back()->with('error', __('Cannot update details of demo user'));
        }

        if ($user->is_suspended == $status) {
            return back()->with('error', __('User already suspended'));
        } else {
            DB::transaction(function () use ($id,$status) {
                UserAdministration::lock();
                $user=User::whereKey($id)->lockForUpdate()->firstOrFail();
                RoleAssignment::protectTarget(auth()->user(),$user);
                UserAdministration::protectLast($user,(string)$status==='0');
                $old=(bool)$user->is_suspended;
                $user->is_suspended=(int)$status;
                $user->save();
                app(AdministrationAudit::class)->record((string)$status==='1'?'user.suspended':'user.activated','user',$user->id,['is_suspended'=>$old],['is_suspended'=>(bool)$status],UserAdministration::auditShop(auth()->user(),$user));
            });
            return back()->with('success', __((string)$status==='1'?'User suspended successfully':'User activated successfully'));
        }
    }

    public function create(StoreUserRequest $request)
    {
        if ($request->isMethod('post')) {
            $selectedRoles=\App\Support\RoleAssignment::resolve($request->user(),$request->input('roles'));
            $newImage=null;
            try {
            DB::transaction(function () use ($request,$selectedRoles,&$newImage) {
            UserAdministration::lock();
            $newUser = new User();
            $newUser->name = $request->name;
            $newUser->email = $request->email;
            $newUser->password = bcrypt($request->password);
            $newUser->username = uniqid();

            if ($request->hasFile("profile_image")) {
                $newImage = $this->fileHandler->fileUploadAndGetPath($request->file("profile_image"), "/public/media/users");
                $newUser->profile_image = $newImage;
            }
            $newUser->save();

            $newUser->syncRoles($selectedRoles->all());
            // A local manager creates an account in their selected, authorized shop.
            $shopId=null;
            if (!RoleAssignment::globalManager($request->user())) {
                $shop=\App\Support\StockContext::shop($request);
                $newUser->pointOfSales()->attach($shop->id,['is_active'=>true]);
                $shopId=$shop->id;
            }
            app(AdministrationAudit::class)->record('user.created','user',$newUser->id,[],['name'=>$newUser->name,'email'=>$newUser->email,'roles'=>$selectedRoles->pluck('name')->all()],$shopId);
            });
            } catch (\Throwable $e) { if($newImage)$this->fileHandler->secureUnlink($newImage); throw $e; }

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

            $newImage=null;
            try {
            $change = DB::transaction(function () use ($request, $id,&$newImage) {
                UserAdministration::lock();
                $user=User::with('roles')->whereKey($id)->lockForUpdate()->firstOrFail();
                RoleAssignment::protectTarget($request->user(),$user);
                $roles = \App\Support\RoleAssignment::resolve($request->user(),$request->input('roles'));
                UserAdministration::protectLast($user,$roles->contains('name','Admin'));
                $before=['name'=>$user->name,'email'=>$user->email,'roles'=>$user->getRoleNames()->all()];

                $oldImage = $user->profile_image;
                if ($request->name !== $user->name) {
                    $user->name = $request->name;
                }
                if ($request->email !== $user->email) {
                    $user->email = $request->email;
                }
                if ($request->filled('password')) {
                    $user->password = bcrypt($request->input('password'));
                }
                if ($request->hasFile('profile_image')) {
                    $newImage = $this->fileHandler->fileUploadAndGetPath($request->file('profile_image'), '/public/media/users');
                    $user->profile_image = $newImage;
                }
                $user->save();
                $user->syncRoles($roles->all());
                app(AdministrationAudit::class)->record('user.updated','user',$user->id,$before,['name'=>$user->name,'email'=>$user->email,'roles'=>$roles->pluck('name')->all(),'password_changed'=>$request->filled('password'),'profile_image_changed'=>$request->hasFile('profile_image')],UserAdministration::auditShop($request->user(),$user));

                return ['allowed' => true, 'old_image' => $request->hasFile('profile_image') ? $oldImage : null];
            });
            } catch (\Throwable $e) { if($newImage)$this->fileHandler->secureUnlink($newImage); throw $e; }

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
        RoleAssignment::protectTarget(auth()->user(),User::findOrFail($id));

        if ($id == auth()->id()) {
            return back()->with('error', __('You cannot delete your own account.'));
        }
        if ($id == 1) {
            return back()->with('error', __('The primary administrator account cannot be deleted.'));
        }

        try {
        $deletionBlock = DB::transaction(function () use ($id) {
            UserAdministration::lock();
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            RoleAssignment::protectTarget(auth()->user(),$user);
            UserAdministration::protectLast($user,false);
            if (
                Order::where('user_id', $user->id)->exists()
                || Purchase::where('user_id', $user->id)->exists()
                || OrderTransaction::where('user_id', $user->id)->exists()
            ) {
                return 'history';
            }
            $shopId=UserAdministration::auditShop(auth()->user(),$user);
            $before=['name'=>$user->name,'email'=>$user->email,'roles'=>$user->getRoleNames()->all()];
            $user->delete();
            app(AdministrationAudit::class)->record('user.deleted','user',$id,$before,[],$shopId);
            return null;
        });
        } catch (\Illuminate\Database\QueryException $e) {
            // Native history uses RESTRICT foreign keys; never delete that evidence.
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw \Illuminate\Validation\ValidationException::withMessages(['user'=>__('administration.user_history')]);
            }
            throw $e;
        }

        if ($deletionBlock === 'last_admin') {
            return back()->with('error', __('The last administrator account cannot be deleted.'));
        }
        if ($deletionBlock === 'history') {
            return back()->with('error', __('Users with sales or purchase history cannot be deleted.'));
        }

        return back()->with('success', __('User deleted successfully'));
    }
}
