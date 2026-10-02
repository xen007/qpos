<?php

namespace App\Http\Controllers\Backend\Product;

use App\Exports\DemoProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\OrderProduct;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ProductUnitService;
use Illuminate\Support\Str;
use App\Support\CatalogueSchema;
use Illuminate\Validation\ValidationException;
use App\Trait\FileHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
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

        if ($request->ajax() && $request->has('draw')) {
            $products = Product::query()->with('unit')->latest();
            return DataTables::of($products)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (image, prix, stock, etat, actions) cote page, sans Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_active', fn($data) => (bool) $data->status)
                ->addColumn('thumb_url', fn($data) => asset('storage/' . $data->image))
                ->addColumn('price_value', fn($data) => $data->discounted_price)
                ->addColumn('price_original', fn($data) => $data->price)
                ->addColumn('quantity_value', fn($data) => $data->quantity)
                ->addColumn('unit_short', fn($data) => optional($data->unit)->short_name)
                // L'URL d'achat porte un parametre de requete : elle est construite
                // ici, car un gabarit « :sku » serait encode en %3A par le routeur.
                ->addColumn('purchase_url', fn($data) => route('backend.admin.purchase.create', ['barcode' => $data->sku]))
            ->addColumn('image', fn($data) => '<img src="' . e(asset('storage/' . $data->image)) . '" loading="lazy" alt="' . e($data->name) . '" class="img-thumb img-fluid" onerror="this.onerror=null; this.src=\'' . e(asset('assets/images/no-image.png')) . '\';" height="80" width="60" />')
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn(
                    'price',
                    fn($data) => $data->discounted_price .
                        ($data->price > $data->discounted_price
                            ? '<br><del>' . $data->price . '</del>'
                            : '')
                )
                ->addColumn('quantity', fn($data) => $data->quantity . ' ' . optional($data->unit)->short_name)
                ->addColumn('created_at', fn($data) => $data->created_at->translatedFormat('d M, Y'))
                ->addColumn('status', fn($data) => $data->status
                    ? '<span class="badge bg-primary">' . e(__('Active')) . '</span>'
                    : '<span class="badge bg-danger">' . e(__('Inactive')) . '</span>')
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group">
                    <button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Actions')) . '</button>
                    <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                      <span class="sr-only">' . e(__('Toggle Dropdown')) . '</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                      <a class="dropdown-item" href="'.route('backend.admin.products.edit', $data->id). '">
                    <i class="fas fa-edit"></i> ' . e(__('Edit')) . '
                </a> <div class="dropdown-divider"></div>
<form action="' . route('backend.admin.products.destroy', $data->id) . '"method="POST" style="display:inline;">
                   ' . csrf_field() . '
                    ' . method_field("DELETE") . '
<button type="submit" class="dropdown-item" onclick="return confirm(\'' . e(__('Are you sure you want to delete this item?')) . '\')"><i class="fas fa-trash"></i> ' . e(__('Delete')) . '</button>
                  </form>
<div class="dropdown-divider"></div>
  <a class="dropdown-item" href="' . route('backend.admin.purchase.create', ['barcode' => $data->sku]) . '">
                <i class="fas fa-cart-plus"></i> ' . e(__('Purchase')) . '
            </a>
                    </div>
                  </div>';
                })
                ->rawColumns(['image', 'price', 'status', 'action'])
                ->toJson();
        }
        if ($request->wantsJson()) {
            $request->validate([
                'search' => 'required|string|max:255',
            ]);

            // Initialize the query
            $products = Product::query();

            // Apply filters based on the search term
            $products = $products->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', "%{$request->search}%")
                    ->orWhere('sku', $request->search);
            });
            // Get the results
            $products = $products->with('unit')->latest()->paginate(20);
            // Return the results as a JSON response
            return ProductResource::collection($products);
        }
        return view('backend.products.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $brands = Brand::whereStatus(true)->get();
        $categories = Category::whereStatus(true)->get();
        $catalogueUnitsReady = CatalogueSchema::ready();
        $units = Unit::query()->when($catalogueUnitsReady, fn ($q) => $q->where('is_active', true))->orderBy('title')->get();
        return view('backend.products.create', compact('brands', 'categories', 'units', 'catalogueUnitsReady'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {

        $validated = $request->validated();
        $validated['sku'] ??= 'P-'.Str::uuid();
        foreach (['purchase_price', 'quantity', 'status'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === null) {
                unset($validated[$field]);
            }
        }
        if (CatalogueSchema::ready()) {
            $validated['allows_fractional'] ??= false;
        }
        $product = DB::transaction(function () use ($validated) {
            if (!CatalogueSchema::ready()) {
                return Product::create($validated);
            }
            if (empty($validated['unit_id'])) {
                $product = Product::create($validated);
                app(\App\Services\ReferencePricingService::class)->sync($product, $validated, true);
                return $product;
            }
            $unit = Unit::whereKey($validated['unit_id'])->where('is_active', true)->lockForUpdate()->first();
            if (!$unit) {
                throw ValidationException::withMessages(['unit_id' => __('Select an active unit.')]);
            }
            $product = Product::create($validated);
            app(ProductUnitService::class)->save($product, [
                'unit_id' => $unit->id, 'code' => 'BASE', 'label' => $unit->title,
                'factor' => '1', 'is_active' => true,
            ]);
            app(\App\Services\ReferencePricingService::class)->sync($product, $validated, true);
            return $product;
        });
        if ($request->hasFile("product_image")) {
            $product->image = $this->fileHandler->fileUploadAndGetPath($request->file("product_image"), "/public/media/products");
            $product->save();
        }

        return redirect()->route('backend.admin.products.index')->with('success', __('Product created successfully!'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['brand', 'category', 'unit'])->findOrFail($id);
        $this->authorize('view', $product);
        return view('backend.products.show', ['product' => $product, 'catalogueUnitsReady' => CatalogueSchema::ready()]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {


        $product = Product::findOrFail($id);
        $brands = Brand::whereStatus(true)->get();
        $categories = Category::whereStatus(true)->orWhere('id', $product->category_id)->get();
        $brands = Brand::whereStatus(true)->orWhere('id', $product->brand_id)->get();
        $catalogueUnitsReady = CatalogueSchema::ready();
        $units = Unit::query()->when($catalogueUnitsReady, fn ($q) => $q->where('is_active', true)->orWhere('id', $product->unit_id))->orderBy('title')->get();
        return view('backend.products.edit', compact('brands', 'categories', 'units', 'product', 'catalogueUnitsReady'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {

        $validated = $request->validated();
        foreach (['sku', 'purchase_price', 'quantity', 'status'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === null) {
                unset($validated[$field]);
            }
        }
        $product = DB::transaction(function () use ($id, $validated) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            if (!CatalogueSchema::ready()) {
                $product->update($validated);
                return $product;
            }
            $unitId = array_key_exists('unit_id', $validated) ? $validated['unit_id'] : $product->unit_id;
            $unit = $unitId ? Unit::whereKey($unitId)->lockForUpdate()->firstOrFail() : null;
            if ($unit && !$unit->is_active && (int) $unit->id !== (int) $product->unit_id) {
                throw ValidationException::withMessages(['unit_id' => __('Select an active unit.')]);
            }
            if ((int) $product->unit_id !== (int) $unitId && $product->productUnits()->exists()) {
                throw ValidationException::withMessages(['unit_id' => __('The base unit cannot change once packagings exist.')]);
            }
            $product->update($validated);
            app(\App\Services\ReferencePricingService::class)->sync($product, $validated);
            return $product;
        });
        $oldImage = $product->image;
        if ($request->hasFile("product_image")) {
            $product->image = $this->fileHandler->fileUploadAndGetPath($request->file("product_image"), "/public/media/products");
            $product->save();
            $this->fileHandler->secureUnlink($oldImage);
        }

        return redirect()->route('backend.admin.products.index')->with('success', __('Product updated successfully!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        $result = DB::transaction(function () use ($id) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            if (OrderProduct::where('product_id', $product->id)->exists() || PurchaseItem::where('product_id', $product->id)->exists()) {
                return ['blocked' => true];
            }

            if (CatalogueSchema::ready() && $product->productUnits()->exists()) {
                return ['packagings' => true];
            }
            $image = $product->image;
            $product->delete();
            return ['blocked' => false, 'image' => $image];
        });

        if (!empty($result['packagings'])) {
            return back()->with('error', __('A product with packagings must be deactivated instead of deleted.'));
        }
        if ($result['blocked']) {
            return redirect()->back()->with('error', __('Products with sales or purchase history cannot be deleted.'));
        }
        if ($result['image']) {
            $this->fileHandler->secureUnlink($result['image']);
        }
        return redirect()->back()->with('success', __('Product Deleted Successfully'));
    }
    public function import(Request $request)
    {
        if ($request->query('download-demo')) {
            return Excel::download(new DemoProductsExport, 'demo_products.xlsx');
        }
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
            ]);

            $supplierId = Supplier::where('name', 'Own Supplier')->value('id');
            abort_if(!$supplierId, 422, __('The default supplier is not configured.'));

            DB::transaction(function () use ($validated, $supplierId) {
                Excel::import(new ProductsImport($supplierId, (int) auth()->id()), $validated['file']);
            });
            return redirect()->back()->with('success', __('Products imported successfully.'));
        }
        return view('backend.products.import');
    }
}
