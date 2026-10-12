<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['old_values'=>'array','new_values'=>'array','occurred_at'=>\App\Casts\UtcDateTime::class,'recorded_at'=>\App\Casts\UtcDateTime::class];

    protected static function booted(): void
    {
        $reject = fn () => throw ValidationException::withMessages(['audit'=>__('administration.audit_immutable')]);
        static::updating($reject);
        static::deleting($reject);
    }
}
