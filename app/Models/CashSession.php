<?php

namespace App\Models;

use App\Casts\SaleDateTime;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    protected $attributes = ['state'=>'open'];
    public function freshTimestamp()
    {
        return now('UTC');
    }

    protected $guarded = [];

    protected $casts = ['created_at' => SaleDateTime::class, 'updated_at' => SaleDateTime::class, 'opened_at' => SaleDateTime::class, 'closed_at' => SaleDateTime::class];

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
