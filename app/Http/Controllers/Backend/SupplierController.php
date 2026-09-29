<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            $suppliers = Supplier::query()->latest();
            return DataTables::of($suppliers)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs actions
                // cote page, en respectant la regle du fournisseur interne.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_default', fn($data) => $data->name === 'Own Supplier')
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('phone', fn($data) => $data->phone)
                ->addColumn('address', fn($data) => $data->address)
                ->addColumn('created_at', fn($data) => $data->created_at->translatedFormat('d M, Y'))
                ->addColumn('action', function ($data) {
                    $isDefaultSupplier = $data->name === 'Own Supplier';
                    return '<div class="btn-group">
                    <button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Actions')) . '</button>
                    <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                      <span class="sr-only">' . e(__('Toggle Dropdown')) . '</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                      <a class="dropdown-item" href="' . route('backend.admin.suppliers.edit', $data->id) . '" ' . ($isDefaultSupplier ? 'onclick="event.preventDefault();" aria-disabled="true"' : '') . ' >
                    <i class="fas fa-edit"></i> ' . e(__('Edit')) . '
                </a> <div class="dropdown-divider"></div>
<form action="' . route('backend.admin.suppliers.destroy', $data->id) . '"method="POST" style="display:inline;">
                   ' . csrf_field() . '
                    ' . method_field("DELETE") . '
<button type="submit" ' . ($isDefaultSupplier ? 'disabled' : '') . ' class="dropdown-item" onclick="return confirm(\'' . e(__('Are you sure you want to delete this item?')) . '\')"><i class="fas fa-trash"></i> ' . e(__('Delete')) . '</button>
                  </form>
                    </div>
                  </div>';
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        if ($request->wantsJson()) {
            return response()->json(Supplier::latest()->get());
        }


        return view('backend.suppliers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        if ($request->wantsJson()) {
            $supplier = Supplier::create([
                'name' => $request->name,
            ]);

            return response()->json($supplier);
        }

        $supplier = Supplier::create($request->only(['name', 'phone', 'address']));

        session()->flash('success', __('Supplier created successfully.'));
        return to_route('backend.admin.suppliers.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('backend.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update($request->only(['name', 'phone', 'address']));

        session()->flash('success', __('Supplier updated successfully.'));
        return to_route('backend.admin.suppliers.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = DB::transaction(function () use ($id) {
            $supplier = Supplier::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($supplier->name === 'Own Supplier') {
                return 'default';
            }
            if (Purchase::where('supplier_id', $supplier->id)->exists()) {
                return 'history';
            }
            $supplier->delete();
            return 'deleted';
        });

        if ($result === 'default') {
            return back()->with('error', __('The default supplier cannot be deleted.'));
        }
        if ($result === 'history') {
            return back()->with('error', __('Suppliers with purchase history cannot be deleted.'));
        }
        session()->flash('success', __('Supplier deleted successfully.'));
        return to_route('backend.admin.suppliers.index');
    }
    public function getCustomers(Request $request)
    {
        abort_if(!auth()->user()->can('supplier_view'), 403);
        if ($request->wantsJson()) {
            return response()->json(Supplier::latest()->get());
        }
    }
    //get orders by supplier id
    public function orders($id)
    {
        abort_if(!auth()->user()->can('supplier_view'), 403);
        $supplier = Supplier::findOrFail($id);
        $orders = $supplier->orders()->paginate(100);
        return view('backend.orders.index', compact('orders'));
    }
}
