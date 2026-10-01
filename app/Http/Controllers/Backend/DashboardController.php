<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Support\DateRange;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->toBase()
            ->selectRaw('COALESCE(SUM(sub_total), 0) as sub_total')
            ->selectRaw('COALESCE(SUM(discount), 0) as discount')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw('COALESCE(SUM(paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(due), 0) as due')
            ->selectRaw('COUNT(*) as total_order')
            ->first();

        $data = [
            'sub_total' => (float) $orders->sub_total,
            'discount' => (float) $orders->discount,
            'total' => (float) $orders->total,
            'paid' => (float) $orders->paid,
            'due' => (float) $orders->due,
            'total_customer' => Customer::count(),
            'total_order' => (int) $orders->total_order,
            'total_product' => Product::count(),
            'total_sale_item' => OrderProduct::sum('quantity'),
        ];


        // Filtre de periode : deux champs date natifs (date_from / date_to), avec
        // compatibilite des anciens liens "daterange". Les bornes couvrent la
        // journee entiere, sans quoi les ventes du jour courant seraient exclues.
        [$startDate, $endDate] = DateRange::resolve($request, ['date_from', 'date_to']);
        $dailyTotals = OrderTransaction::selectRaw('DATE(created_at) as date, SUM(amount) as total_amount')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'DESC')
        ->get();
        $dates = $dailyTotals->pluck('date')->toArray();
        $totalAmounts = $dailyTotals->pluck('total_amount')->toArray();
        $data['dates'] = $dates;
        $data['totalAmounts'] = $totalAmounts;
        $data['dateFrom'] = $startDate->format('Y-m-d');
        $data['dateTo'] = $endDate->format('Y-m-d');


        $currentYear = now()->year;
        $data['currentYear'] = $currentYear;

        $salesData = OrderTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total_amount')
        ->whereYear('created_at', $currentYear)
        ->groupBy('month')
        ->orderBy('month', 'ASC')->pluck('total_amount', 'month')->toArray();
        $tempMonths = [];
        $tempTotalAmountMonth = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthKey = Carbon::create($currentYear, $i, 1)->format('Y-m');
            $tempMonths[] = $monthKey;
            $tempTotalAmountMonth[] = $salesData[$monthKey] ?? 0;
        }

        $data['months'] = $tempMonths;
        $data['totalAmountMonth'] = $tempTotalAmountMonth;

        return view('backend.index', $data);
    }

    public function profile()
    {
        $user = auth()->user();
        return view('backend.profile.index', compact('user'));
    }
}
