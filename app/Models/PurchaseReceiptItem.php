<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptItem extends Model
{
    protected $guarded = [];
    protected $casts = ['factor_used' => 'decimal:6', 'entered_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6', 'source_unit_cost' => 'decimal:6', 'unit_cost' => 'decimal:6', 'source_line_amount' => 'decimal:6', 'expires_on' => 'date:Y-m-d'];
    public function receipt() { return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id'); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
}
