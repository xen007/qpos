<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            // 'barcode' => $this->barcode,
            'price' => $this->price,
            'purchase_price' => $this->purchase_price,
            'discounted_price' => $this->discounted_price,
            'quantity' => $this->quantity,
            'stock_state'=>app(\App\Services\StockPolicyService::class)->classify((string)($this->quantity??'0'),app(\App\Services\StockPolicyService::class)->effective((int)$request->attributes->get('point_of_sale')?->id,$this->id))['state'],
            'expired_quantity' => $this->when(isset($this->stock_expired), $this->stock_expired),
            'allows_fractional' => $this->allows_fractional,
            'known_expiry_classification' => $this->known_expiry_classification,
            'product_units' => $this->whenLoaded('productUnits', fn () => $this->productUnits
                ->where('is_active', true)->values()->map(fn ($unit) => [
                    'id' => $unit->id, 'label' => $unit->label, 'code' => $unit->code,
                    'factor' => $unit->factor, 'is_reference' => $unit->is_reference,
                    'unit_cost' => $unit->reference_purchase_cost,
                    'sale_price' => $unit->sale_price_ttc,
                ])),
            'status' => $this->status,
            'created_at' => $this->created_at,
            // 'image_url' => $this->getImageUrl(),
        ];
    }
}
