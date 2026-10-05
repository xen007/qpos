<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{

    public function saleReport(Request $request)
    {

        // Periode demandee (start_date / end_date) ; une valeur invalide est ignoree
        // et le defaut (30 derniers jours) s'applique.
        [$start_date, $end_date] = DateRange::resolve($request);

        // Retrieve orders within the date range
        $orders = Order::whereBetween('created_at', [$start_date, $end_date])->with('customer')->get();

        // Calculate totals
        $data = [
            'orders' => $orders,
            'sub_total' => $orders->sum('sub_total'),
            'discount' => $orders->sum('discount'),
            'paid' => $orders->sum('paid'),
            'due' => $orders->sum('due'),
            'total' => $orders->sum('total'),
            'start_date' => $start_date->translatedFormat('d M Y'),
            'end_date' => $end_date->translatedFormat('d M Y'),
            // Valeurs brutes (Y-m-d) pour alimenter le filtre de la page.
            'start_date_input' => $start_date->format('Y-m-d'),
            'end_date_input' => $end_date->format('Y-m-d'),
        ];

        return view('backend.reports.sale-report', $data);
    }
    public function saleSummery(Request $request)
    {

        // Periode demandee (start_date / end_date) ; une valeur invalide est ignoree
        // et le defaut (30 derniers jours) s'applique.
        [$start_date, $end_date] = DateRange::resolve($request);

        // Retrieve orders within the date range
        $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();

        // Calculate totals
        $data = [
            'sub_total' => $orders->sum('sub_total'),
            'discount' => $orders->sum('discount'),
            'paid' => $orders->sum('paid'),
            'due' => $orders->sum('due'),
            'total' => $orders->sum('total'),
            'start_date' => $start_date->translatedFormat('d M Y'),
            'end_date' => $end_date->translatedFormat('d M Y'),
            // Valeurs brutes (Y-m-d) pour alimenter le filtre de la page ; les cles
            // start_date / end_date restent les libelles affiches dans le rapport.
            'start_date_input' => $start_date->format('Y-m-d'),
            'end_date_input' => $end_date->format('Y-m-d'),
        ];

        return view('backend.reports.sale-summery', $data);
    }

    function inventoryReport(Request $request)
    {

        if ($request->ajax()) {
            $products = Product::query()->with('unit')->latest()->active();
            $shop = \App\Support\StockContext::shop($request);
            app(\App\Services\StockAvailability::class)->attach($products,$shop->id);
            return DataTables::of($products)
                ->addIndexColumn()
                // Colonnes neutres : la page migree compose le prix (avec le prix
                // d'origine barre) et le stock avec son unite, sans markup HTML
                // dans le JSON. Les colonnes historiques restent inchangees.
                ->addColumn('price_value', fn($data) => $data->discounted_price)
                ->addColumn('price_original', fn($data) => $data->price)
                ->addColumn('quantity_value', fn($data) => $data->quantity)
                ->addColumn('unit_short', fn($data) => optional($data->unit)->short_name)
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('sku', fn($data) => $data->sku)
                ->addColumn(
                    'price',
                    fn($data) => $data->discounted_price .
                        ($data->price > $data->discounted_price
                            ? '<br><del>' . $data->price . '</del>'
                            : '')
                )
                ->addColumn('quantity', fn($data) => $data->quantity . ' ' . optional($data->unit)->short_name)
                ->rawColumns(['status'])
                ->toJson();
        }
        return view('backend.reports.inventory');
    }
}
