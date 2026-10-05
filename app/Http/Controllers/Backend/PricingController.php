<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\PriceRule;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Services\PricingService;
use App\Support\MoneyDecimal;
use App\Support\PointOfSaleContext;
use App\Support\PricingSchema;
use App\Support\QuantityDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PricingController extends Controller
{
    private function ready(): void
    {
        abort_unless(PricingSchema::ready(), 503, __('Pricing is unavailable until its migrations are applied.'));
    }
    private function model(string $entity): string
    {
        return match ($entity) { 'rules' => PriceRule::class, 'promotions' => Promotion::class, default => abort(404) };
    }
    private function authorizeScope(?int $shopId): void
    {
        if ($shopId === null) {
            abort_unless(auth()->user()->can('point_of_sale_manage_all'), 403);
        } else {
            Gate::authorize('inspect', PointOfSale::findOrFail($shopId));
        }
    }
    private function scoped($query)
    {
        if (!auth()->user()->can('point_of_sale_manage_all')) {
            $ids = PointOfSaleContext::manageableBy(auth()->user())->select('points_of_sale.id');
            $query->where(fn ($q) => $q->whereNull('point_of_sale_id')->orWhereIn('point_of_sale_id', $ids));
        }
        return $query;
    }
    public function index(Request $request)
    {
        $this->ready();
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        $units = ProductUnit::with('product')->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('product_id')->orderBy('factor')->orderBy('id')->paginate(20, ['*'], 'units_page')->withQueryString();
        $rules = $this->scoped(PriceRule::with(['productUnit.product', 'pointOfSale', 'customer'])->whereHas('productUnit.product', fn ($q) => $q->where('name', 'like', '%'.$search.'%')))->latest()->paginate(20, ['*'], 'rules_page')->withQueryString();
        $promotions = $this->scoped(Promotion::with(['productUnit.product', 'pointOfSale', 'customer'])->whereHas('productUnit.product', fn ($q) => $q->where('name', 'like', '%'.$search.'%')))->latest()->paginate(20, ['*'], 'promotions_page')->withQueryString();
        $shops = PointOfSaleContext::manageableBy($request->user())->orderBy('name')->get();
        $customers = $request->user()->can('customer_view') ? Customer::select(['id', 'name'])->orderBy('name')->get() : collect();
        return view('backend.pricing.index', compact('units', 'rules', 'promotions', 'shops', 'customers', 'search'));
    }
    public function store(Request $request, string $entity)
    {
        $this->ready();
        $data = $this->validated($request, $entity);
        $this->authorizeScope($data['point_of_sale_id']);
        $model = $this->model($entity);
        $model::create($data);
        return back()->with('success', __('Pricing rule saved.'));
    }
    public function update(Request $request, string $entity, int $id)
    {
        $this->ready();
        $model = $this->model($entity);
        $data = $this->validated($request, $entity);
        DB::transaction(function () use ($model, $id, $data, $entity) {
            $row = $model::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->authorizeScope($row->point_of_sale_id);
            $this->authorizeScope($data['point_of_sale_id']);
            if ($entity === 'promotions' && $row->legacy_product_id && !in_array($data['kind'], ['fixed', 'percentage'], true)) {
                throw ValidationException::withMessages(['kind' => __('Product discounts support fixed amounts or percentages.')]);
            }
            $row->update($data);
        });
        return back()->with('success', __('Pricing rule saved.'));
    }
    public function destroy(string $entity, int $id)
    {
        $this->ready();
        $model = $this->model($entity);
        DB::transaction(function () use ($model, $id) {
            $row = $model::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->authorizeScope($row->point_of_sale_id);
            $row->update(['is_active' => false]);
        });
        return back()->with('success', __('Pricing rule deactivated.'));
    }
    public function packaging(Request $request, int $id)
    {
        $this->ready();
        $data = $request->validate(['sale_price_ttc' => ['required', 'string', 'max:32'], 'reference_purchase_cost' => ['nullable', 'string', 'max:32']]);
        $price = (string) MoneyDecimal::parse($data['sale_price_ttc'], 'sale_price_ttc');
        $hasCost = array_key_exists('reference_purchase_cost', $data);
        if ($hasCost) {
            abort_unless($request->user()->hasRole('Admin'), 403);
        }
        $cost = $hasCost && $data['reference_purchase_cost'] !== null ? (string) MoneyDecimal::parse($data['reference_purchase_cost'], 'reference_purchase_cost') : null;
        DB::transaction(function () use ($id, $price, $hasCost, $cost) {
            $unit = ProductUnit::findOrFail($id);
            $product = Product::whereKey($unit->product_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $product);
            $unit = ProductUnit::whereKey($id)->lockForUpdate()->firstOrFail();
            $values = ['sale_price_ttc' => $price];
            if ($hasCost) { $values['reference_purchase_cost'] = $cost; }
            $unit->update($values);
            // Compatibility only: native DECIMAL columns remain authoritative.
            if ($unit->is_reference) {
                $native = ['catalogue_price_ttc' => $price];
                if ($hasCost) { $native['catalogue_reference_cost'] = $cost; }
                $product->forceFill($native)->save();
                $legacy = [];
                if (MoneyDecimal::parse($price)->isLessThanOrEqualTo('99999999.99')) { $legacy['price'] = (string) MoneyDecimal::parse($price)->toScale(2, \Brick\Math\RoundingMode::HalfUp); }
                if ($hasCost && $cost !== null && MoneyDecimal::parse($cost)->isLessThanOrEqualTo('99999999.99')) { $legacy['purchase_price'] = (string) MoneyDecimal::parse($cost)->toScale(2, \Brick\Math\RoundingMode::HalfUp); }
                if ($legacy) { $product->update($legacy); }
            }
        });
        return back()->with('success', __('Packaging prices saved.'));
    }
    public function quote(Request $request, PricingService $service)
    {
        $this->ready();
        $data = $request->validate([
            'product_unit_id' => ['required', 'integer', Rule::exists('product_units', 'id')],
            'quantity' => ['required', 'string', 'max:32'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
        ]);
        $shop = $request->attributes->get('point_of_sale');
        abort_unless($shop, 422, __('Select an assigned active store first.'));
        Gate::authorize('view', $shop);
        if (!empty($data['customer_id'])) { abort_unless($request->user()->can('customer_view'), 403); }
        $unit = ProductUnit::findOrFail($data['product_unit_id']);
        Gate::authorize('view', $unit->product);
        $quote = $service->quote($unit, $data['quantity'], $shop->id, $data['customer_id'] ?? null);
        return back()->with('pricing_quote', $quote);
    }
    private function validated(Request $request, string $entity): array
    {
        $this->model($entity);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_unit_id' => ['required', 'integer', Rule::exists('product_units', 'id')],
            'scope_point_of_sale_id' => ['nullable', 'integer', Rule::exists('points_of_sale', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'minimum_quantity' => ['required', 'string', 'max:32'],
            'priority' => ['required', 'integer', 'min:-2147483648', 'max:2147483647'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), ['after_or_equal:starts_at'])],
            'is_active' => ['required', 'boolean'],
            'price_ttc' => [Rule::requiredIf($entity === 'rules'), 'nullable', 'string', 'max:32'],
            'kind' => [Rule::requiredIf($entity === 'promotions'), 'nullable', Rule::in(['percentage', 'fixed', 'quantity', 'bundle'])],
            'value' => ['nullable', 'string', 'max:32'],
            'buy_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000000', 'required_if:kind,quantity'],
            'free_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000000', 'required_if:kind,quantity'],
            'bundle_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000000', 'required_if:kind,bundle'],
            'bundle_price' => ['nullable', 'string', 'max:32', 'required_if:kind,bundle'],
        ]);
        if (!empty($data['customer_id'])) { abort_unless($request->user()->can('customer_view'), 403); }
        $data['point_of_sale_id'] = $data['scope_point_of_sale_id'] ?? null;
        $data['customer_id'] = $data['customer_id'] ?? null;
        unset($data['scope_point_of_sale_id']);
        $data['minimum_quantity'] = (string) QuantityDecimal::parse($data['minimum_quantity'], 'minimum_quantity')->toScale(6);
        $fields = ['name', 'product_unit_id', 'point_of_sale_id', 'customer_id', 'minimum_quantity', 'priority', 'starts_at', 'ends_at', 'is_active'];
        if ($entity === 'rules') {
            $data['price_ttc'] = (string) MoneyDecimal::parse($data['price_ttc'], 'price_ttc');
            $fields[] = 'price_ttc';
        } else {
            $data['value'] = (string) MoneyDecimal::parse($data['value'] ?? '0', 'value');
            if ($data['kind'] === 'percentage' && MoneyDecimal::parse($data['value'])->isGreaterThan('100')) {
                throw ValidationException::withMessages(['value' => __('A percentage discount cannot exceed 100.')]);
            }
            $data['bundle_price'] = isset($data['bundle_price']) ? (string) MoneyDecimal::parse($data['bundle_price'], 'bundle_price') : null;
            if ($data['kind'] === 'bundle' && $data['bundle_price'] === null) { $data['bundle_price'] = '0.000000'; }
            foreach (['buy_quantity', 'free_quantity', 'bundle_quantity'] as $field) { $data[$field] ??= null; }
            $fields = array_merge($fields, ['kind', 'value', 'buy_quantity', 'free_quantity', 'bundle_quantity', 'bundle_price']);
        }
        return array_intersect_key($data, array_flip($fields));
    }
}
