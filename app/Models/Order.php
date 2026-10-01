<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPSTORM_META\map;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['total_item'];
    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }
    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class);
    }
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function getTotalItemAttribute()
    {
        if (array_key_exists('item_quantity_sum', $this->attributes)) {
            return (int) $this->attributes['item_quantity_sum'];
        }

        return $this->products()->sum('quantity');
    }
   
}
