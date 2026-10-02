<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class CatalogueConversionController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('catalogue_conversion_runs'), 503);
        $run = DB::table('catalogue_conversion_runs')->latest('completed_at')->first();
        $issues = DB::table('catalogue_conversion_issues as issues')->leftJoin('products', 'products.id', '=', 'issues.source_id')
            ->where('issues.run_id', $run?->id)->select(['issues.*', 'products.name as product_name'])
            ->orderBy('issues.id')->paginate(20);
        return view('backend.pricing.conversion', compact('run', 'issues'));
    }
}
