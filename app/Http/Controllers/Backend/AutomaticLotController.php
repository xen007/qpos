<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductBatch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomaticLotController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBatch::query()->with('product:id,name,sku')->where('auto_generated', true)
            ->withSum(['stocks as on_hand' => fn ($q) => $q->where('point_of_sale_id', 1)], 'saleable_quantity');
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('batch_number', 'like', '%'.$request->string('search').'%')
            ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->string('search').'%')->orWhere('sku', 'like', '%'.$request->string('search').'%')));
        if ($request->filled('cost_unknown')) $query->where('cost_unknown', $request->boolean('cost_unknown'));
        if ($request->filled('estimated_expiry')) $query->where('estimated_expiry', $request->boolean('estimated_expiry'));
        $batches = $query->orderByDesc('id')->paginate(25)->withQueryString();
        return view('backend.stock.automatic-lots', compact('batches'));
    }

    public function update(Request $request, ProductBatch $batch, StockService $stock)
    {
        abort_unless($batch->auto_generated, 404);
        $data = $request->validate([
            'batch_number' => ['required','string','max:255'],
            'unit_cost' => ['required','numeric','min:0','max:999999999999.999999'],
            'cost_unknown' => ['required','boolean'], 'currency_code' => ['nullable', Rule::in(['XAF','BDT'])],
            'expiry_status' => ['required', Rule::in(['dated','not_applicable','unknown'])],
            'expires_on' => ['nullable','date_format:Y-m-d'], 'estimated_expiry' => ['required','boolean'],
            'reason' => ['required','string','max:5000'], 'operation_key' => ['required','string','max:64'],
        ]);
        $stock->amendAutomaticLot($batch->id, $data, (int) $request->user()->id);
        return back()->with('success', __('Automatic lot correction recorded.'));
    }
}
