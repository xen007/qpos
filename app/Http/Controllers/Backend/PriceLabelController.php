<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Services\PricingService;
use App\Support\Code128;
use App\Support\SaleOperation as Op;
use App\Support\StockContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PriceLabelController extends Controller
{
    public function index(Request $r)
    {
        StockContext::shop($r);
        $units = ProductUnit::with('product')->where('is_active', true)->whereNotNull('sale_price_ttc')->orderBy('product_id')->paginate(50);

        return view('backend.phase4.labels', compact('units'));
    }

    public function pdf(Request $r)
    {
        $data = $r->validate(['unit_ids' => 'required|array|min:1|max:100', 'unit_ids.*' => 'required|integer|distinct', 'copies' => 'required|integer|min:1|max:20', 'format' => 'required|in:avery-l7160,avery-5160,thermal-58,thermal-80']);
        $shop = StockContext::shop($r)->id;
        $formats = ['avery-l7160' => ['page' => [210, 297], 'width' => 63.5, 'height' => 38.1, 'columns' => 3, 'rows' => 7, 'top' => 15.15, 'left' => 7.25, 'gap' => 2.5], 'avery-5160' => ['page' => [215.9, 279.4], 'width' => 66.675, 'height' => 25.4, 'columns' => 3, 'rows' => 10, 'top' => 12.7, 'left' => 4.7625, 'gap' => 3.175], 'thermal-58' => ['page' => [58, 40], 'width' => 54, 'height' => 36, 'columns' => 1, 'rows' => 1, 'top' => 2, 'left' => 2, 'gap' => 0], 'thermal-80' => ['page' => [80, 50], 'width' => 76, 'height' => 46, 'columns' => 1, 'rows' => 1, 'top' => 2, 'left' => 2, 'gap' => 0]];
        $format = $formats[$data['format']];
        $labels = [];
        $units = ProductUnit::with(['product', 'barcodes'])->whereIn('id', $data['unit_ids'])->get();
        if ($units->count() !== count($data['unit_ids'])) {
            Op::fail('unit_ids', 'A selected packaging no longer exists.');
        }
        foreach ($units as $unit) {
            $q = app(PricingService::class)->quote($unit, '1', $shop, null, now('Africa/Douala'));
            $code = $unit->barcodes->firstWhere('is_active', true)?->barcode;
            if (! $code && $unit->is_reference) {
                $code = $unit->product->sku;
            }
            if (! $code) {
                Op::fail('unit_ids', 'Assign a barcode to every selected packaging.');
            }
            if (($format['width'] - 4) * 0.95 / (strlen($code) * 11 + 55) < 0.19) {
                Op::fail('unit_ids', 'This barcode is too long for the selected label format.');
            }
            $svg = Code128::svg($code);
            for ($i = 0; $i < $data['copies']; $i++) {
                $labels[] = ['name' => $q['product_label'], 'price' => rtrim(rtrim($q['total_ttc'], '0'), '.'), 'code' => $code, 'barcode' => 'data:image/svg+xml;base64,'.base64_encode($svg)];
            }
        }
        $pdf = Pdf::loadView('backend.phase4.label-pdf', compact('labels', 'format'))->setPaper([0, 0, $format['page'][0] * 72 / 25.4, $format['page'][1] * 72 / 25.4]);

        return $pdf->download('qpos-price-labels.pdf');
    }
}
