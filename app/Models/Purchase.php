<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_of_sale_id',
        'due_date',
        'receipt_status',
        'payment_status',
        'currency_code',
        'operation_key',
        'request_hash',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'supplier_id',
        'user_id',
        'sub_total',
        'tax',
        'discount_value',
        'discount_type',
        'shipping',
        'grand_total',
        'status',
        'date',
    ];
    protected $table = 'purchases';
    protected $casts = ['sub_total'=>'decimal:6','tax'=>'decimal:6','discount_value'=>'decimal:6','shipping'=>'decimal:6','grand_total'=>'decimal:6','cancelled_at'=>\App\Casts\StockDateTime::class];
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
    public function receipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
