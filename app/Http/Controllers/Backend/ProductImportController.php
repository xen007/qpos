<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\ProductImportService;
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImportController extends Controller
{
    public function index(Request $request, ProductImportService $service)
    {
        if ($request->query('download-demo')) return $this->csv('products-template.csv',[
            ProductImportService::HEADERS,
            ['NEW-001','Example product','Piece','100','0','0','','','','0','fixed','1','not_applicable','',''],
        ]);
        $report=null;
        if ($request->isMethod('post')) {
            $data=$request->validate([
                'file'=>'required|file|mimes:csv,txt|max:5120','action'=>'required|in:preview,apply,errors',
                'import_mode'=>'required|in:catalogue,receipt','supplier_id'=>'nullable|integer',
                'evidence'=>'nullable|string|max:5000','operation_key'=>'required|string|max:64','preview_hash'=>'nullable|string|size:64',
                'resolutions'=>'nullable|array','resolutions.*'=>'in:ignore,suffix,update',
            ]);
            $context=['shop_id'=>(int)StockContext::shop($request)->id,'user_id'=>(int)$request->user()->id,
                'mode'=>$data['import_mode'],'supplier_id'=>empty($data['supplier_id']) ? null : (int)$data['supplier_id'],'evidence'=>$data['evidence'] ?? '',
                'resolutions'=>$data['resolutions'] ?? []];
            $path=$request->file('file')->getRealPath();
            $report=$service->preview($path,$context);
            if ($data['action']==='errors') {
                $lines=[['line','sku','field','message']];
                foreach ($report['errors'] as $error) $lines[]=array_values($error);
                return $this->csv('import-errors-'.now('Africa/Douala')->format('Ymd-His').'.csv',$lines);
            }
            $previewHash=hash('sha256',hash_file('sha256',$path).json_encode($context,JSON_THROW_ON_ERROR));
            if ($data['action']==='apply') {
                if (!hash_equals($previewHash,$data['preview_hash'] ?? '')) throw ValidationException::withMessages(['file'=>__('Preview this exact file and receipt context before applying.')]);
                if ($report['valid'] || \Illuminate\Support\Facades\DB::table('product_import_runs')->where('operation_key',$data['operation_key'])->exists()) {
                    $run=$service->apply($path,$context,$data['operation_key']);
                    return to_route('backend.admin.products.import')->with('success',__('Import applied atomically.').' #'.$run->id);
                }
            }
        }
        return view('backend.products.import',[
            'report'=>$report,'previewHash'=>$previewHash ?? null,'operationKey'=>$request->input('operation_key') ?: (string)Str::uuid(),
            'suppliers'=>Supplier::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    private function csv(string $name,array $rows)
    {
        // Downloads must contain CSV only, including when PHP emitted a
        // buffered startup notice before Laravel handled the request.
        while (ob_get_level()>0) {
            $flags=ob_get_status()['flags'] ?? 0;
            if (($flags & PHP_OUTPUT_HANDLER_REMOVABLE)===0) {
                if (($flags & PHP_OUTPUT_HANDLER_CLEANABLE)!==0) ob_clean();
                break;
            }
            ob_end_clean();
        }
        return response()->streamDownload(function () use ($rows) {
            $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
            foreach ($rows as $row) {
                $row=array_map(fn($v)=>preg_match('/^[=+\-@\t\r]/',(string)$v) ? "'".$v : $v,$row);
                fputcsv($out,$row,',','"','');
            }
            fclose($out);
        },$name,['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);
    }
}
