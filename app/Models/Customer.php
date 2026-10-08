<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function isWalking(): bool
    {
        return $this->internal_code === 'walking';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
