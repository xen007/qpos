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
            $shop = $request->attributes->get('point_of_sale');
            $products = Product::query()->with('unit')->latest();
            if ($shop) { app(\App\Services\StockAvailability::class)->attach($products, $shop->id); }
            return DataTables::of($products)
                ->filter(function ($query) use ($request) {
                    $search = $request->input('search.value');
                    if (is_string($search) && trim($search) !== '') {
                        $term = '%'.trim($search).'%';
                        $query->where(fn ($q) => $q->where('products.name', 'like', $term)->orWhere('products.sku', 'like', $term));
                    }
                })
                ->order(function ($query) use ($request, $shop) {
                    $fields = [
                        'name' => 'products.name', 'created_at' => 'products.created_at', 'is_active' => 'products.status',
                        'price_value' => "COALESCE(products.catalogue_price_ttc, ROUND(products.price - CASE WHEN products.discount_type = 'fixed' THEN products.discount WHEN products.discount_type = 'percentage' THEN products.price * products.discount / 100 ELSE 0 END, 2))",
                        'quantity_value' => $shop ? 'COALESCE(stock_available, 0)' : 'products.quantity',
                    ];
                    $query->reorder();
                    foreach (array_slice((array) $request->input('order', []), 0, 8) as $order) {
                        $index = filter_var($order['column'] ?? null, FILTER_VALIDATE_INT);
                        $field = $index !== false ? $request->input('columns.'.$index.'.data') : null;
                        if (is_string($field) && isset($fields[$field]) && in_array($order['dir'] ?? null, ['asc', 'desc'], true)) {
                            $query->orderByRaw($fields[$field].' '.$order['dir']);
                        }
                    }
                    $query->orderBy('products.id', 'desc');
                })
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (image, prix, stock, etat, actions) cote page, sans Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_active', fn($data) => (bool) $data->status)
                ->addColumn('thumb_url', fn($data) => asset('storage/' . $data->image))
                ->addColumn('price_value', fn($data) => $data->catalogue_price_ttc ?? $data->discounted_price)
                ->addColumn('price_original', fn($data) => $data->catalogue_price_ttc ?? $data->price)
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
            $shop = \App\Support\StockContext::shop($request);
            app(\App\Services\StockAvailability::class)->attach($products,$shop->id);

            // Apply filters based on the search term
            $products = $products->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', "%{$request->search}%")
                    ->orWhere('sku', $request->search);
            });
            // Get the results
            $products = $products->with(['unit', 'productUnits' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_reference')])->latest()->paginate(20);
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
        $product = \App\Support\CatalogueCodes::transaction(function () use ($validated) {
            \App\Support\CatalogueCodes::validateSku($validated['sku']);
            if (!CatalogueSchema::ready()) {
                return Product::create(\App\Support\LegacyMoney::compatible($validated));
            }
            if (empty($validated['unit_id'])) {
                $product = Product::create(\App\Support\LegacyMoney::compatible($validated));
                app(\App\Services\ReferencePricingService::class)->sync($product, $validated, true);
                return $product;
            }
            $unit = Unit::whereKey($validated['unit_id'])->where('is_active', true)->lockForUpdate()->first();
            if (!$unit) {
                throw ValidationException::withMessages(['unit_id' => __('Select an active unit.')]);
            }
            $validated['allows_fractional'] ??= \App\Support\FractionalQuantityRule::classify($unit);
            $product = Product::create(\App\Support\LegacyMoney::compatible($validated));
            if ($product->allows_fractional !== null) {
                app(ProductUnitService::class)->save($product, [
                    'unit_id' => $unit->id, 'code' => 'BASE', 'label' => $unit->title,
                    'factor' => '1', 'is_active' => true,
                ]);
            }
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
        if (\App\Support\PricingSchema::ready()) { $product->load('legacyPromotion'); }
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
        $product = \App\Support\CatalogueCodes::transaction(function () use ($id, $validated) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            if (isset($validated['sku'])) { \App\Support\CatalogueCodes::validateSku($validated['sku'], $product->id); }
            if (!CatalogueSchema::ready()) {
                $product->update(\App\Support\LegacyMoney::compatible($validated, $product));
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
            if (array_key_exists('allows_fractional', $validated) && $validated['allows_fractional'] === null && $unit) {
                $validated['allows_fractional'] = \App\Support\FractionalQuantityRule::classify($unit);
            }
            $product->update(\App\Support\LegacyMoney::compatible($validated, $product));
            if ($unit && $product->allows_fractional !== null && !$product->productUnits()->where('is_reference', true)->exists()) {
                app(ProductUnitService::class)->save($product, [
                    'unit_id' => $unit->id, 'code' => 'BASE', 'label' => $unit->title, 'factor' => '1', 'is_active' => true,
                ]);
            }
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

        $result = \App\Support\CatalogueCodes::transaction(function () use ($id) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            if (OrderProduct::where('product_id', $product->id)->exists() || PurchaseItem::where('product_id', $product->id)->exists()
                || $product->stockMovements()->exists() || \Illuminate\Support\Facades\DB::table('stock_opening_sources')->where('product_id',$product->id)->exists()) {
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
        return app(\App\Http\Controllers\Backend\ProductImportController::class)->index($request, app(\App\Services\ProductImportService::class));
    }
}
