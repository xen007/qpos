<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'short_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
