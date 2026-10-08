<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Backend\Pos\OrderController;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $customers = Customer::query()->latest();

            return DataTables::of($customers)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs actions
                // cote page, en respectant les memes regles (permissions,
                // client protege).
                ->addColumn('id', fn ($data) => $data->id)
                ->addColumn('is_default', fn ($data) => $data->isWalking())
                ->addColumn('name', fn ($data) => $data->name)
                ->addColumn('phone', fn ($data) => $data->phone)
                ->addColumn('address', fn ($data) => $data->address)
                ->addColumn('created_at', fn ($data) => $data->created_at->translatedFormat('d M, Y'))
                ->addColumn('action', function ($data) {
                    $isDefaultCustomer = $data->isWalking();
                    $actionHtml = '<div class="btn-group">
        <button type="button" class="btn bg-gradient-primary btn-flat">'.e(__('Actions')).'</button>
        <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
            <span class="sr-only">'.e(__('Toggle Dropdown')).'</span>
        </button>
        <div class="dropdown-menu" role="menu">';

                    // Check if the user has permission to update customers
                    if (auth()->user()->can('customer_update')) {
                        $actionHtml .= '<a class="dropdown-item" href="'.route('backend.admin.customers.edit', $data->id).'" '.($isDefaultCustomer ? 'onclick="event.preventDefault();" aria-disabled="true"' : '').'>
            <i class="fas fa-edit"></i> '.e(__('Edit')).'
        </a>';
                        $actionHtml .= '<div class="dropdown-divider"></div>';
                    }

                    // Check if the user has permission to delete customers
                    if (auth()->user()->can('customer_delete')) {
                        $actionHtml .= '<form action="'.route('backend.admin.customers.destroy', $data->id).'" method="POST" style="display:inline;">
            '.csrf_field().'
            '.method_field('DELETE').'
            <button type="submit" '.($isDefaultCustomer ? 'disabled' : '').' class="dropdown-item" onclick="return confirm(\''.e(__('Are you sure you want to delete this item?')).'\')">
                <i class="fas fa-trash"></i> '.e(__('Delete')).'
            </button>
        </form>';
                        $actionHtml .= '<div class="dropdown-divider"></div>';
                    }

                    if (auth()->user()->can('customer_sales')) {
                        $actionHtml .= '<a class="dropdown-item" href="'.route('backend.admin.customers.orders', $data->id).'">
        <i class="fas fa-cart-plus"></i> '.e(__('Sales')).'
    </a>';
                    }

                    $actionHtml .= '</div></div>';

                    return $actionHtml;
                })

                ->rawColumns(['action'])
                ->toJson();
        }

        return view('backend.customers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return view('backend.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        if ($request->wantsJson()) {
            $customer = Customer::create([
                'name' => $request->name,
            ]);

            return response()->json($customer);
        }

        $customer = Customer::create($request->only(['name', 'phone', 'address']));

        session()->flash('success', __('Customer created successfully.'));

        return to_route('backend.admin.customers.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return view('backend.phase4.customer-profile', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $customer = Customer::findOrFail($id);
        $this->authorize('update', $customer);

        return view('backend.customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $this->authorize('update', $customer);
        $customer->update($request->only(['name', 'phone', 'address', 'is_active']));

        session()->flash('success', __('Customer updated successfully.'));

        return to_route('backend.admin.customers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        $result = DB::transaction(function () use ($id) {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($customer->isWalking()) {
                return 'default';
            }
            if ($customer->orders()->exists() || DB::table('price_rules')->where('customer_id', $id)->exists() || DB::table('promotions')->where('customer_id', $id)->exists()) {
                $customer->update(['is_active' => false]);

                return 'history';
            }
            $customer->delete();

            return 'deleted';
        });

        if ($result === 'default') {
            return back()->with('error', __('The default walk-in customer cannot be deleted.'));
        }
        if ($result === 'history') {
            return back()->with('success', __('Customer deactivated; financial history retained.'));
        }
        session()->flash('success', __('Customer deleted successfully.'));

        return to_route('backend.admin.customers.index');
    }

    public function getCustomers(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(Customer::where('is_active', true)->latest()->get());
        }
    }

    // get orders by customer id
    public function orders(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('viewSales', $customer);

        return app(OrderController::class)->customerOrders($request, $customer);
    }
}
