<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\DataTables;

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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:currencies,code',
            'symbol' => 'required|string'
        ]);
        $currency = Currency::create($request->only(['name', 'code', 'symbol']));

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

        $currency = Currency::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:currencies,code,' . $currency->id,
            'symbol' => 'required|string'
        ]);
        $currency->update($request->only(['name', 'code', 'symbol']));
        return redirect()->route('backend.admin.currencies.index')->with('success', __('Currency updated successfully!'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        $currency = Currency::findOrFail($id);
        $currency->delete();
        return redirect()->back()->with('success', __('Currency Deleted Successfully'));
    }
    public function setDefault($id)
    {
        Currency::where('active', true)->update(['active' => false]);
        $currency = Currency::findOrFail($id);
        $currency->active = true;
        $currency->save();
        Cache::put('default_currency', $currency, 60 * 24);
        return redirect()->back()->with('success', __('Currency Set Default Successfully'));
    }
}
