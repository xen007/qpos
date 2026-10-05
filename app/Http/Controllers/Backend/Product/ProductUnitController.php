<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Services\ProductUnitService;
use App\Support\QuantityDecimal;
use App\Support\CatalogueSchema;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductUnitController extends Controller
{
    public function index(Product $product)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $packagings = $product->productUnits()->with(['unit', 'barcodes'])->orderByDesc('is_reference')->orderBy('id')->paginate(20);
        $units = Unit::where('is_active', true)->orWhere('id', $product->unit_id)
            ->orWhereIn('id', $product->productUnits()->select('unit_id'))->orderBy('title')->get();
        return view('backend.products.units', compact('product', 'packagings', 'units'));
    }

    public function store(Request $request, Product $product, ProductUnitService $service)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $service->save($product, $this->validated($request));
        return back()->with('success', __('Packaging saved successfully.'));
    }

    public function update(Request $request, Product $product, int $productUnit, ProductUnitService $service)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $packaging = $product->productUnits()->findOrFail($productUnit);
        $service->save($product, $this->validated($request), $packaging);
        return back()->with('success', __('Packaging saved successfully.'));
    }

    public function convert(Request $request, Product $product, int $productUnit)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $packaging = $product->productUnits()->where('is_active', true)->findOrFail($productUnit);
        if ($product->allows_fractional === null) {
            throw ValidationException::withMessages(['quantity' => __('Set the fractional quantity rule on the product first.')]);
        }
        $request->validate(['quantity' => ['required', 'string', 'max:32']]);
        $result = QuantityDecimal::toBase($request->input('quantity'), $packaging->factor, $product->allows_fractional);
        return back()->with('success', __('Base quantity: :quantity', ['quantity' => $result]));
    }

    public function barcode(Request $request, Product $product, int $productUnit)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $packaging = $product->productUnits()->findOrFail($productUnit);
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:128', 'regex:/\A[!-~]+\z/D', Rule::unique('product_barcodes', 'barcode')],
        ]);
        \App\Support\CatalogueCodes::transaction(function () use ($product, $packaging, $data) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $legacyMatch = Product::where('sku', $data['barcode'])->first();
            if ($legacyMatch && (!$packaging->is_reference || $legacyMatch->id !== $product->id)) {
                throw ValidationException::withMessages(['barcode' => __('This barcode conflicts with an existing product SKU.')]);
            }
            if (ProductBarcode::where('barcode', $data['barcode'])->exists()) {
                throw ValidationException::withMessages(['barcode' => __('This barcode is already in use.')]);
            }
            try {
                $packaging->barcodes()->create($data + ['is_active' => true]);
            } catch (QueryException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    throw ValidationException::withMessages(['barcode' => __('This barcode is already in use.')]);
                }
                throw $exception;
            }
        });
        return back()->with('success', __('Barcode saved successfully.'));
    }

    public function barcodeStatus(Request $request, Product $product, int $productUnit, int $barcode)
    {
        $this->authorize('update', $product);
        abort_unless(CatalogueSchema::ready(), 503, __('Packagings are unavailable until the catalogue migration is applied.'));
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $product->productUnits()->findOrFail($productUnit)->barcodes()->findOrFail($barcode)->update($data);
        return back()->with('success', __('Barcode saved successfully.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'code' => ['required', 'string', 'max:64', 'regex:/\A[A-Za-z0-9_-]+\z/D'],
            'label' => ['required', 'string', 'max:255'],
            'factor' => ['required', 'string', 'max:32'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
