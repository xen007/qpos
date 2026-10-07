<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'image',
        'name',
        'slug',
        'sku',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'allows_fractional',
        'price',
        'discount',
        'discount_type',
        'purchase_price',
        'quantity',
        'expire_date',
        'status',
        'sku_auto_suffix',
        'expiry_policy_override',
    ];
    protected $appends = ['discounted_price'];
    protected $casts = ['allows_fractional' => 'boolean', 'catalogue_price_ttc' => 'decimal:6', 'catalogue_reference_cost' => 'decimal:6', 'catalogue_discount' => 'decimal:6'];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (($product->exists && $product->isDirty('quantity'))
                || (!$product->exists && !\Brick\Math\BigDecimal::of((string)($product->getAttributes()['quantity'] ?? '0'))->isZero())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['quantity'=>__('Stock quantities are managed through receipts and stock adjustments.')]);
            }
        });
    }

    public function getQuantityAttribute($legacy): mixed
    {
        if (array_key_exists('stock_available', $this->attributes)) {
            return $this->attributes['stock_available'] ?? '0.000000';
        }
        // Console conversion commands preserve the frozen legacy source value.
        if (app()->runningInConsole()) { return $legacy; }
        $shop = request()->attributes->get('point_of_sale');
        return $shop ? app(\App\Services\StockService::class)->available((int)$shop->id,(int)$this->id) : null;
    }

    public function productUnits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function legacyPromotion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Promotion::class, 'legacy_product_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!$this->exists || empty($this->attributes['slug'])) {
            $this->attributes['slug'] = $this->generateSlug($value);
        }
    }

    /**
     * Generate a unique slug for the category.
     *
     * @param string $name
     * @return string
     */
    protected function generateSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', "$slug%")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function scopeStocked($query)
    {
        $shop = \App\Support\StockContext::shop(request());
        return app(\App\Services\StockAvailability::class)->attach($query,$shop->id)->having('stock_available','>=',1);
    }
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_type == 'fixed') {
            $discountedPrice = $this->price - $this->discount;
        } elseif ($this->discount_type == 'percentage') {
            $discountedPrice = $this->price - ($this->price * $this->discount / 100);
        } else {
            $discountedPrice = $this->price;
        }
        return round($discountedPrice, 2);
    }
}
