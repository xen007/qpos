<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name','phone', 'address', 'is_internal', 'is_active'];
    protected $casts = ['is_internal' => 'boolean', 'is_active' => 'boolean'];
    protected $table = 'suppliers';
    public function orders()
    {
        return $this->purchases();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
