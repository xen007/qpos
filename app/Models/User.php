<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'google_id',
        'profile_image',
        'is_google_registered',
        'is_suspended',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public $appends = ['pro_pic'];

    public function getProPicAttribute()
    {
        return imageRecover($this->profile_image);
    }

    public function pointOfSales(): BelongsToMany
    {
        return $this->belongsToMany(PointOfSale::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function hasActivePointOfSale(int $pointOfSaleId): bool
    {
        return $this->pointOfSales()
            ->where('points_of_sale.id', $pointOfSaleId)
            ->where('points_of_sale.is_active', true)
            ->where('point_of_sale_user.is_active', true)
            ->exists();
    }
}
