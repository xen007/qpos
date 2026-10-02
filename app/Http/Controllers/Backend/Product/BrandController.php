<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Trait\FileHandler;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BrandController extends Controller
{
    public $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $brands = Brand::query()->latest();
            return DataTables::of($brands)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees vers Tailwind composent
                // leurs cellules (actions, etat) cote page, sans markup Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_active', fn($data) => (bool) $data->status)
                ->addColumn('image', fn($data) => '<img src="' . e(asset('storage/' . $data->image)) . '" loading="lazy" alt="' . e($data->name) . '" class="img-thumb img-fluid" onerror="this.onerror=null; this.src=\'' . asset('assets/images/no-image.png') . '\';" height="80" width="60" />')
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('status', fn($data) => $data->status
                    ? '<span class="badge bg-primary">' . e(__('Active')) . '</span>'
                    : '<span class="badge bg-danger">' . e(__('Inactive')) . '</span>')
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group">
                    <button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Action')) . '</button>
                    <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                      <span class="sr-only">' . e(__('Toggle Dropdown')) . '</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                      <a class="dropdown-item" href="' . route('backend.admin.brands.edit', $data->id) . '" ' . ' >
                    <i class="fas fa-edit"></i> ' . e(__('Edit')) . '
                </a> <div class="dropdown-divider"></div>
<form action="' . route('backend.admin.brands.destroy', $data->id) . '"method="POST" style="display:inline;">
                   ' . csrf_field() . '
                    ' . method_field("DELETE") . '
<button type="submit" class="dropdown-item" onclick="return confirm(\'' . e(__('Are you sure you want to delete this item?')) . '\')"><i class="fas fa-trash"></i> ' . e(__('Delete')) . '</button>
                  </form>
                  </div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->toJson();
        }


        return view('backend.brands.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ]);
        $brand = Brand::create(collect($validated)->except('brand_image')->all());
        if ($request->hasFile("brand_image")) {
            $brand->image = $this->fileHandler->fileUploadAndGetPath($request->file("brand_image"), "/public/media/brands");
            $brand->save();
        }

        return redirect()->route('backend.admin.brands.index')->with('success', __('Brand created successfully!'));
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

        $brand = Brand::findOrFail($id);
        return view('backend.brands.edit', compact(  'brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ]);
        $brand = Brand::findOrFail($id);
        $oldImage = $brand->image;
        $brand->update(collect($validated)->except('brand_image')->all());
        if ($request->hasFile("brand_image")) {
            $brand->image = $this->fileHandler->fileUploadAndGetPath($request->file("brand_image"), "/public/media/brands");
            $brand->save();
            $this->fileHandler->secureUnlink($oldImage);
        }

        return redirect()->route('backend.admin.brands.index')->with('success', __('Brand updated successfully!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = DB::transaction(function () use ($id) {
            $brand = Brand::whereKey($id)->lockForUpdate()->firstOrFail();
            if (Product::where('brand_id', $id)->exists()) {
                return ['blocked' => true];
            }
            $image = $brand->image;
            $brand->delete();
            return ['blocked' => false, 'image' => $image];
        });
        if ($result['blocked']) {
            return back()->with('error', __('A brand or category used by products must be deactivated instead of deleted.'));
        }
        if ($result['image']) {
            $this->fileHandler->secureUnlink($result['image']);
        }
        return redirect()->back()->with('success', __('Brand Deleted Successfully'));
    }
}
