<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingsController extends Controller
{
    public function edit()
    {
        return view('backend.settings.system', [
            'multiShopEnabled'=>config('system.multi_shop_enabled',true),
            'defaultExpiryMonths'=>config('system.default_expiry_months',12),
            'autoGenerateLots'=>config('system.auto_generate_lots',true),
            'defaultLotPrefix'=>config('system.default_lot_prefix','LOT-AUTO'),
            'allowSaleWithoutLot'=>config('system.allow_sale_without_lot',true),
        ]);
    }

    public function update(Request $request)
    {
        $data=$request->validate([
            'multi_shop_enabled'=>['required','boolean'], 'default_expiry_months'=>['required','integer','min:1','max:120'],
            'auto_generate_lots'=>['required','boolean'], 'default_lot_prefix'=>['required','string','max:32','regex:/\A[A-Za-z0-9_-]+\z/'],
            'allow_sale_without_lot'=>['required','boolean'],
        ]);
        $newMulti=(bool)$data['multi_shop_enabled'];
        if (!$newMulti && config('system.multi_shop_enabled',true) && $this->hasNonMainBusinessData()) {
            return back()->withErrors(['multi_shop_enabled'=>__('Mono-boutique is refused because another shop contains business data. No data was changed.')])->withInput();
        }
        foreach ($data as $key=>$value) writeConfig($key,$value);
        return back()->with('success',__('System settings saved.'));
    }

    private function hasNonMainBusinessData(): bool
    {
        foreach (['product_stock','batch_stock','stock_movements','orders','purchases','payments','stock_transfers','inventories','purchase_receipts','stock_opening_approvals','product_import_runs'] as $table) {
            if (!Schema::hasTable($table)) continue;
            foreach (['point_of_sale_id','source_point_of_sale_id','destination_point_of_sale_id','source_shop_id','destination_shop_id'] as $column) {
                if (!Schema::hasColumn($table,$column)) continue;
                $query=DB::table($table)->where($column,'<>',1);
                if ($table==='product_stock') $query->where(fn($q)=>$q->where('saleable_quantity','<>',0)->orWhere('unsaleable_quantity','<>',0)->orWhere('in_transit_quantity','<>',0)->orWhere('unallocated_opening_quantity','<>',0));
                if ($table==='batch_stock') $query->where(fn($q)=>$q->where('saleable_quantity','<>',0)->orWhere('unsaleable_quantity','<>',0));
                if ($query->exists()) return true;
            }
        }
        return false;
    }
}
