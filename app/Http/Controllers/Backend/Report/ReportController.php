<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Backend\ReportingController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function saleReport(Request $r) { return app(ReportingController::class)->index($r, 'sales'); }
    public function saleSummery(Request $r) { return app(ReportingController::class)->index($r, 'summary'); }
    public function inventoryReport(Request $r) { return app(ReportingController::class)->index($r, 'stock'); }
}