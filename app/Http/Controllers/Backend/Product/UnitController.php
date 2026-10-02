<?php
namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\CatalogueSchema;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Unit::query())->addIndexColumn()
                ->addColumn('action', fn () => '')
                ->addColumn('state_label', fn ($unit) => !CatalogueSchema::ready() || $unit->is_active ? __('Active') : __('Inactive'))
                ->toJson();
        }
        return view('backend.units.index', ['catalogueUnitsReady' => CatalogueSchema::ready()]);
    }
    public function create()
    {
        return view('backend.units.create', ['catalogueUnitsReady' => CatalogueSchema::ready()]);
    }
    public function store(Request $request)
    {
        Unit::create($this->validated($request));
        return to_route('backend.admin.units.index')->with('success', __('Unit created successfully!'));
    }
    public function show($id)
    {
        return to_route('backend.admin.units.index');
    }
    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        return view('backend.units.edit', ['unit' => $unit, 'catalogueUnitsReady' => CatalogueSchema::ready()]);
    }
    public function update(Request $request, $id)
    {
        Unit::findOrFail($id)->update($this->validated($request));
        return to_route('backend.admin.units.index')->with('success', __('Unit updated successfully!'));
    }
    public function destroy($id)
    {
        // Conserver les references du catalogue et de l'historique.
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        Unit::findOrFail($id)->update(['is_active' => false]);
        return back()->with('success', __('Unit deactivated successfully.'));
    }
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:255'],
            'is_active' => [Rule::excludeIf(!CatalogueSchema::ready()), 'required', 'boolean'],
        ]);
    }
}