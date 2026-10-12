<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\{DB,Schema};
use App\Services\AdministrationAudit;
use App\Support\RoleAssignment;
use Illuminate\Validation\ValidationException;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $currencies = Currency::latest()->get();
            return DataTables::of($currencies)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (actions, devise par defaut) cote page, sans markup Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_active', fn($data) => (bool) $data->active)
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('code', fn($data) => $data->code)
                ->addColumn('symbol', fn($data) => $data->symbol
                    . ($data->active ? ' (' . e(__('Default Currency')) . ')' : ''))
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group">
                    <button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Action')) . '</button>
                    <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                      <span class="sr-only">' . e(__('Toggle Dropdown')) . '</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                      <a class="dropdown-item" href="' . route('backend.admin.currencies.edit', $data->id) . '" ' . ' >
                    <i class="fas fa-edit"></i> ' . e(__('Edit')) . '
                </a> <div class="dropdown-divider"></div>
<form action="' . route('backend.admin.currencies.destroy', $data->id) . '"method="POST" style="display:inline;">
                   ' . csrf_field() . '
                    ' . method_field("DELETE") . '
<button type="submit" class="dropdown-item" onclick="return confirm(\'' . e(__('Are you sure you want to delete this item?')) . '\')"><i class="fas fa-trash"></i> ' . e(__('Delete')) . '</button>
                  </form><div class="dropdown-divider"></div>
                   <form action="' . route('backend.admin.currencies.setDefault', $data->id) . '" method="POST">
                    ' . csrf_field() . '
                    <button type="submit" class="dropdown-item" onclick="return confirm(\'' . e(__('Are you sure you want to set this currency as default?')) . '\')">
                    <i class="fas fa-edit"></i> ' . e(__('Set Default')) . '
                    </button>
                   </form>
                  </div>';
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        return view('backend.settings.currencies.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return view('backend.settings.currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|regex:/\A[A-Z]{3}\z/|unique:currencies,code',
            'symbol' => 'required|string|max:20',
        ]);
        DB::transaction(function () use ($validated) {
            $this->lockCurrencies();
            $currency=Currency::create($validated);
            app(AdministrationAudit::class)->record('currency.created','currency',$currency->id,[], $validated);
        });
        Cache::forget('default_currency');

        return redirect()->route('backend.admin.currencies.index')->with('success', __('Currency created successfully!'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {


        $currency = Currency::findOrFail($id);
        return view('backend.settings.currencies.edit', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        abort_unless(RoleAssignment::globalManager($request->user()),403);
        $currency = Currency::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|regex:/\A[A-Z]{3}\z/|unique:currencies,code,' . $currency->id,
            'symbol' => 'required|string|max:20',
        ]);
        DB::transaction(function () use ($id,$validated) {
            $this->lockCurrencies();
            $currency=Currency::findOrFail($id);
            if($currency->code!==$validated['code'] && ($currency->active || $this->used($currency->code))) {
                throw ValidationException::withMessages(['code'=>__('administration.currency_used')]);
            }
            $before=$currency->only(['name','code','symbol']);
            $currency->update($validated);
            app(AdministrationAudit::class)->record('currency.updated','currency',$id,$before,$validated);
        });
        Cache::forget('default_currency');
        return redirect()->route('backend.admin.currencies.index')->with('success', __('Currency updated successfully!'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        abort_unless(RoleAssignment::globalManager(auth()->user()),403);
        DB::transaction(function () use ($id) {
            $this->lockCurrencies();
            $currency=Currency::findOrFail($id);
            if($currency->active) throw ValidationException::withMessages(['currency'=>__('administration.currency_default')]);
            if($this->used($currency->code)) throw ValidationException::withMessages(['currency'=>__('administration.currency_used')]);
            $before=$currency->only(['name','code','symbol']);$currency->delete();
            app(AdministrationAudit::class)->record('currency.deleted','currency',$id,$before);
        });
        Cache::forget('default_currency');
        return redirect()->back()->with('success', __('Currency Deleted Successfully'));
    }
    public function setDefault($id)
    {
        abort_unless(RoleAssignment::globalManager(auth()->user()),403);
        DB::transaction(function () use ($id) {
            $this->lockCurrencies();
            $currency=Currency::findOrFail($id);
            if($currency->code!=='XAF') throw ValidationException::withMessages(['currency'=>__('administration.xaf_default')]);
            $before=['code'=>Currency::where('active',true)->value('code')];
            Currency::where('active',true)->update(['active'=>false]);
            $currency->active=true;$currency->save();
            app(AdministrationAudit::class)->record('currency.default_changed','currency',$id,$before,['code'=>$currency->code,'active'=>true]);
        });
        Cache::forget('default_currency');
        return redirect()->back()->with('success', __('Currency Set Default Successfully'));
    }

    private function lockCurrencies(): void
    {
        Currency::orderBy('id')->lockForUpdate()->get();
    }

    private function used(string $code): bool
    {
        // BDT denotes preserved historical documents, including legacy unknown rows.
        if(in_array($code,['XAF','BDT'],true))return true;
        foreach(['orders','purchases','payments','product_batches','credit_notes'] as $table) {
            if(Schema::hasColumn($table,'currency_code') && DB::table($table)->where('currency_code',$code)->exists())return true;
        }
        return false;
    }
}
