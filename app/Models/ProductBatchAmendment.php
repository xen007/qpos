<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatchAmendment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'occurred_at' => 'datetime',
    ];
}
