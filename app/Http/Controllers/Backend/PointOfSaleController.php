<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PointOfSale;
use App\Models\User;
use App\Support\PointOfSaleContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PointOfSaleController extends Controller
{
    private function ready(): void
    {
        abort_unless(PointOfSaleContext::ready(), 503, __('Stores are unavailable until their migrations are applied.'));
    }
    public function index(Request $request)
    {
        $this->ready();
        $this->authorize('viewAny', PointOfSale::class);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = $data['search'] ?? '';
        $shops = PointOfSaleContext::manageableBy($request->user())
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('backend.shops.index', compact('shops', 'search'));
    }
    public function create()
    {
        $this->ready();
        $this->authorize('create', PointOfSale::class);
        return $this->form(new PointOfSale());
    }
    public function store(Request $request)
    {
        $this->ready();
        $this->authorize('create', PointOfSale::class);
        $data = $this->validated($request);
        if ($request->boolean('assignment_payload')) {
            $this->authorize('assignNew', PointOfSale::class);
        }
        DB::transaction(function () use ($request, $data) {
            $shop = PointOfSale::create(collect($data)->only(['code', 'name', 'address', 'is_active'])->all());
            if ($request->boolean('assignment_payload')) {
                $this->assignUsers($shop, $data['user_ids'] ?? []);
            }
            $this->settings($request,$shop);
        });
        return to_route('backend.admin.shops.index')->with('success', __('Store saved successfully.'));
    }
    public function show($shop)
    {
        $this->ready();
        $shop = PointOfSale::findOrFail($shop);
        $this->authorize('inspect', $shop);
        return view('backend.shops.show', compact('shop'));
    }
    public function edit($shop)
    {
        $this->ready();
        $shop = PointOfSale::findOrFail($shop);
        $this->authorize('update', $shop);
        return $this->form($shop);
    }
    public function update(Request $request, $shop)
    {
        $this->ready();
        $shop = PointOfSale::findOrFail($shop);
        $this->authorize('update', $shop);
        $data = $this->validated($request, $shop);
        if ($request->boolean('assignment_payload')) {
            $this->authorize('assign', $shop);
        }
        DB::transaction(function () use ($request, $shop, $data) {
            $shop = PointOfSale::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $this->authorize('update', $shop);
            if(!$data['is_active']) $this->assertDeactivation($shop);
            if ($request->boolean('assignment_payload')) {
                $this->authorize('assign', $shop);
            }
            $shop->update(collect($data)->only(['code', 'name', 'address', 'is_active'])->all());
            if ($request->boolean('assignment_payload')) {
                $this->assignUsers($shop, $data['user_ids'] ?? []);
            }
            $this->settings($request,$shop);
        });
        return to_route('backend.admin.shops.index')->with('success', __('Store saved successfully.'));
    }
    public function destroy($shop)
    {
        $this->ready();
        $shop = PointOfSale::findOrFail($shop);
        DB::transaction(function () use ($shop) {
            $shop = PointOfSale::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $this->authorize('delete', $shop);
            $this->assertDeactivation($shop);
            $shop->update(['is_active' => false]);
        });
        return to_route('backend.admin.shops.index')->with('success', __('Store deactivated; its history is preserved.'));
    }
    public function select(Request $request)
    {
        $this->ready();
        $data = $request->validate(['point_of_sale_id' => ['required', 'integer', Rule::exists('points_of_sale', 'id')]]);
        $shop = PointOfSale::findOrFail($data['point_of_sale_id']);
        $this->authorize('view', $shop);
        $request->session()->put('active_point_of_sale_id', $shop->id);
        $request->user()->forceFill(['preferred_point_of_sale_id' => $shop->id])->save();
        return back()->with('success', __('Active store changed.'));
    }
    private function validated(Request $request, ?PointOfSale $shop = null): array
    {
        $data = $request->validate([
            'code' => [$shop ? 'required' : 'nullable', 'string', 'max:64', 'regex:/\A[A-Za-z0-9_-]+\z/D', Rule::unique('points_of_sale', 'code')->ignore($shop?->id)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'assignment_payload' => ['nullable', 'boolean'],
            'user_ids' => ['nullable', 'array', 'max:500'],
            'user_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('is_suspended', false)],
        ]);

        if (!$shop && blank($data['code'] ?? null)) {
            $data['code'] = $this->uniqueCodeFor($data['name']);
        }

        return $data;
    }
    private function uniqueCodeFor(string $name): string
    {
        preg_match_all('/[A-Za-z0-9]+/', Str::ascii($name), $matches);
        $words = $matches[0] ?? [];
        $base = count($words) > 1
            ? implode('', array_map(fn ($word) => $word[0], $words))
            : substr($words[0] ?? 'SHOP', 0, 3);
        $base = strtoupper(substr($base ?: 'SHOP', 0, 50));
        $candidate = $base;
        $suffix = 2;

        while (PointOfSale::where('code', $candidate)->exists()) {
            $ending = '-'.$suffix++;
            $candidate = substr($base, 0, 64 - strlen($ending)).$ending;
        }

        return $candidate;
    }
    private function assignUsers(PointOfSale $shop, array $ids): void
    {
        $shop->users()->syncWithoutDetaching(array_fill_keys($ids, ['is_active' => true]));
        $shop->users()->newPivotQuery()->where('point_of_sale_id', $shop->id)
            ->whereNotIn('user_id', $ids)->update(['is_active' => false, 'updated_at' => now()]);
    }
    private function form(PointOfSale $shop)
    {
        $canAssign = $shop->exists ? Gate::allows('assign', $shop) : Gate::allows('assignNew', PointOfSale::class);
        $users = $canAssign ? User::where('is_suspended', false)->select(['id', 'name'])->orderBy('name')->get() : collect();
        $assignedIds = $shop->exists ? $shop->users()->where('point_of_sale_user.is_active', true)->pluck('users.id')->all() : [];
        $workflow=app(\App\Services\ShopWorkflowSettings::class)->get((int)$shop->id);
        $methods=\Illuminate\Support\Facades\Schema::hasTable('payment_methods') ? DB::table('payment_methods')->where('sale_enabled',true)->get() : collect();
        $activeMethods=$shop->exists ? array_keys(app(\App\Services\PaymentMethodService::class)->choices($shop->id)) : $methods->where('default_active',true)->pluck('code')->all();
        return view('backend.shops.form', compact('shop', 'users', 'assignedIds', 'canAssign','workflow','methods','activeMethods'));
    }
    private function settings(Request $request, PointOfSale $shop): void
    {
        if (!$request->boolean('workflow_payload')) return;
        abort_unless($request->user()->can('payment_methods_manage') && \Illuminate\Support\Facades\Schema::hasTable('point_of_sale_settings'),403);
        $v=$request->validate(['pending_sale_enabled'=>'required|boolean','pending_expiry_minutes'=>'required|integer|min:15|max:1440','taken_lease_minutes'=>'required|integer|min:1|max:60','orphan_idle_minutes'=>'required|integer|min:60|max:10080','payment_codes'=>'required|array|min:1|max:7','payment_codes.*'=>'required|string|distinct|in:cash,card,bank_transfer,orange_money,mtn_momo,wave,cheque']);
        if(!$v['pending_sale_enabled'] && DB::table('pending_sales')->where('point_of_sale_id',$shop->id)->whereIn('state',['pending','taken'])->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['pending_sale_enabled'=>__('Resolve pending sales before disabling handoff.')]);
        DB::table('point_of_sale_settings')->updateOrInsert(['point_of_sale_id'=>$shop->id],collect($v)->except('payment_codes')->all()+['updated_at'=>now('UTC')]);
        foreach (DB::table('payment_methods')->where('sale_enabled',true)->get() as $method) DB::table('point_of_sale_payment_method')->updateOrInsert(['point_of_sale_id'=>$shop->id,'payment_method_id'=>$method->id],['is_active'=>in_array($method->code,$v['payment_codes'],true)]);
    }
    private function assertDeactivation(PointOfSale $shop): void
    {
        $open=DB::table('cash_sessions')->where('point_of_sale_id',$shop->id)->where('state','open')->exists();
        $stock=DB::table('product_stock')->where('point_of_sale_id',$shop->id)->where(fn($q)=>$q->where('saleable_quantity','>',0)->orWhere('unsaleable_quantity','>',0))->exists();
        $pending=\Illuminate\Support\Facades\Schema::hasTable('pending_sales')&&DB::table('pending_sales')->where('point_of_sale_id',$shop->id)->whereIn('state',['pending','taken'])->exists();
        if($open||$stock||$pending) throw \Illuminate\Validation\ValidationException::withMessages(['is_active'=>__('Resolve open cash sessions, pending sales and remaining stock before deactivating this store.')]);
    }
}
