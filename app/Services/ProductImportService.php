<?php
namespace App\Services;

use App\Models\{Brand,Category,PointOfSale,Product,ProductBarcode,Supplier,Unit,User};
use App\Support\{CatalogueCodes,FractionalQuantityRule,MoneyDecimal,QuantityDecimal};
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ProductImportService
{
    public const HEADERS = ['sku','name','unit','price','purchase_price','quantity','brand','category','description','discount','discount_type','status','expiry_status','expire_date','batch_number'];

    /** Pure read: no products, reports, receipts or files are persisted by preview. */
    public function preview(string $path, array $context): array
    {
        $bytes=file_get_contents($path);
        if ($bytes===false || strlen($bytes)>5*1024*1024 || !mb_check_encoding($bytes,'UTF-8') || str_contains($bytes,"\0"))
            return $this->failure(1,'file',__('Use a UTF-8 CSV file of at most 5 MB.'));
        $bytes=preg_replace('/^\xEF\xBB\xBF/','',$bytes);
        $first=strtok($bytes,"\r\n");
        $separator=',';
        foreach ([',',';',"\t"] as $candidate) if (count(str_getcsv($first ?: '',$candidate,'"',''))>count(str_getcsv($first ?: '',$separator,'"',''))) $separator=$candidate;
        if (($invalidLine=$this->invalidQuotesLine($bytes,$separator))!==null)
            return $this->failure($invalidLine,'file',__('Malformed CSV quoting.'));
        $stream=fopen('php://temp','w+'); fwrite($stream,$bytes); rewind($stream);
        $headers=fgetcsv($stream,0,$separator,'"','');
        if (!$headers) { fclose($stream); return $this->failure(1,'headers',__('Missing CSV headers.')); }
        $headers=array_map(fn($v)=>trim((string)$v),$headers);
        if (count(array_unique($headers))!==count($headers) || array_diff($headers,self::HEADERS) || array_diff(['sku','name','price'],$headers)) {
            fclose($stream); return $this->failure(1,'headers',__('Invalid, duplicate or missing CSV headers.'));
        }
        $rows=[]; $errors=[]; $line=1;
        while (true) {
            $start=ftell($stream); $rowLine=$line+1;
            $values=fgetcsv($stream,0,$separator,'"','');
            if ($values===false) break;
            $line+=max(1,substr_count(substr($bytes,$start,ftell($stream)-$start),"\n"));
            if ($values===[null] || (count($values)===1 && trim((string)$values[0])==='')) continue;
            if (count($rows)>=2000) { $errors[]=['line'=>$rowLine,'sku'=>'','field'=>'file','message'=>__('Maximum 2000 product rows.')]; break; }
            if (count($values)!==count($headers)) { $errors[]=['line'=>$rowLine,'sku'=>'','field'=>'columns','message'=>__('The column count does not match the header.')]; continue; }
            $rows[]=['line'=>$rowLine,'values'=>array_combine($headers,array_map(fn($v)=>trim((string)$v),$values))];
        }
        fclose($stream);
        $result=$this->previewRows($rows,$context);
        $result['errors']=array_merge($errors,$result['errors']); $result['valid']=$result['errors']===[];
        return $result;
    }

    public function previewRows(array $rows, array $context): array
    {
        $errors=[]; $seen=[]; $normalized=[];
        $user=User::findOrFail($context['user_id']);
        abort_unless($user->can('product_import'),403);
        $shop=PointOfSale::accessibleBy($user)->find($context['shop_id']);
        if (!$shop) return $this->failure(1,'shop',__('Choose an active assigned shop.'));
        $mode=$context['mode'] ?? 'catalogue';
        if (!in_array($mode,['catalogue','receipt'],true)) return $this->failure(1,'mode',__('Choose catalogue or documented receipt.'));
        if ($mode==='receipt' && (!$user->can('purchase_receive') || trim($context['evidence'] ?? '')==='' || !Supplier::whereKey($context['supplier_id'] ?? 0)->where('is_active',true)->exists()))
            return $this->failure(1,'receipt',__('A receipt requires permission, an active supplier and documentary evidence.'));
        foreach ($rows as $entry) {
            $row=$entry['values']; $line=$entry['line'];
            $hasReceiptCost=array_key_exists('purchase_price',$row) && $row['purchase_price']!=='';
            $row += ['unit'=>'','quantity'=>'0','purchase_price'=>'0','discount'=>'0','discount_type'=>'fixed','status'=>'1','expiry_status'=>'unknown','expire_date'=>'','batch_number'=>''];
            foreach (['quantity','purchase_price','discount','discount_type','status','expiry_status'] as $key) if ($row[$key]==='') $row[$key]=match($key) {'discount_type'=>'fixed','status'=>'1','expiry_status'=>'unknown',default=>'0'};
            try {
                Validator::make($row,[
                    'sku'=>'required|string|max:255','name'=>'required|string|max:255','unit'=>'nullable|string|max:255',
                    'brand'=>'nullable|string|max:255','category'=>'nullable|string|max:255','description'=>'nullable|string|max:5000',
                    'status'=>'required|in:0,1','discount_type'=>'required|in:fixed,percentage',
                    'expiry_status'=>'required|in:dated,not_applicable,unknown','expire_date'=>'nullable|date_format:Y-m-d','batch_number'=>'nullable|string|max:255',
                ])->validate();
                // Match the catalogue's MariaDB collation, including accent/case
                // equivalence, rather than accepting duplicates in the preview.
                $skuKey=DB::getDriverName()==='mysql'
                    ? DB::selectOne('SELECT HEX(WEIGHT_STRING(CAST(? AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci)) AS sku_weight',[$row['sku']])->sku_weight
                    : mb_strtolower($row['sku'],'UTF-8');
                if (isset($seen[$skuKey])) throw ValidationException::withMessages(['sku'=>__('Duplicate SKU in this file.')]);
                $seen[$skuKey]=true;
                if (Product::where('sku',$row['sku'])->exists())
                    throw ValidationException::withMessages(['sku'=>__('This SKU already exists. It will not be renamed or updated.')]);
                CatalogueCodes::validateSku($row['sku']);
                $units=empty($row['unit']) ? collect() : Unit::where('title',$row['unit'])->where('is_active',true)->get();
                if (!empty($row['unit']) && $units->count()!==1) throw ValidationException::withMessages(['unit'=>__('Choose one existing active unit with an unambiguous name.')]);
                $fractional=$units->isNotEmpty() ? FractionalQuantityRule::classify($units->first()) : null;
                $quantity=QuantityDecimal::parse($row['quantity']);
                if ($quantity->isGreaterThan('1000000')) throw ValidationException::withMessages(['quantity'=>__('Maximum import quantity is 1000000.')]);
                if ($quantity->isPositive()) {
                    if ($mode==='receipt' && !$hasReceiptCost)
                        throw ValidationException::withMessages(['purchase_price'=>__('Provide the source receipt cost explicitly; zero must be stated, not inferred.')]);
                    if ($fractional===null) throw ValidationException::withMessages(['unit'=>__('Configure a known fractional quantity rule for this unit first.')]);
                    QuantityDecimal::toBase($row['quantity'],'1',$fractional);
                }
                foreach (['price','purchase_price','discount'] as $field) {
                    $money=MoneyDecimal::parse($row[$field],$field);
                    // Native precision is retained; compatibility columns still
                    // have the historical DOUBLE(10,2) range.
                    if ($money->isGreaterThan('99999999.99'))
                        throw ValidationException::withMessages([$field=>__('The amount is outside the supported range.')]);
                    $row[$field]=(string)$money;
                }
                $discount=BigDecimal::of($row['discount']);
                if ($discount->isGreaterThan($row['discount_type']==='percentage' ? '100' : $row['price']))
                    throw ValidationException::withMessages(['discount'=>__('The discount exceeds the supported amount.')]);
                if ($mode==='catalogue' && !$quantity->isZero()) throw ValidationException::withMessages(['quantity'=>__('Catalogue import must have zero stock quantity.')]);
                if ($row['expiry_status']==='dated' ? $row['expire_date']==='' : $row['expire_date']!=='')
                    throw ValidationException::withMessages(['expiry_status'=>__('A date is required only for dated expiry.')]);
                if ($mode==='receipt' && $quantity->isPositive() && $row['batch_number']==='')
                    throw ValidationException::withMessages(['batch_number'=>__('Documented stock receipts require a lot identifier.')]);
                MoneyDecimal::rounded(BigDecimal::of($row['purchase_price'])->multipliedBy($quantity));
                $row['unit_id']=$units->first()?->id; $row['allows_fractional']=$fractional;
                $normalized[]=['line'=>$line,'values'=>$row];
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field=>$messages) foreach ($messages as $message) $errors[]=['line'=>$line,'sku'=>$row['sku'] ?? '','field'=>$field,'message'=>$message];
            }
        }
        if (!$rows) $errors[]=['line'=>1,'sku'=>'','field'=>'file','message'=>__('The CSV contains no product rows.')];
        return ['valid'=>$errors===[],'errors'=>$errors,'rows'=>$normalized,'line_count'=>count($rows)];
    }

    public function apply(string $path, array $context, string $operationKey): object
    {
        $actor=User::findOrFail($context['user_id']);
        abort_unless($actor->can('product_import') && PointOfSale::accessibleBy($actor)->whereKey($context['shop_id'])->exists(),403);
        if (!preg_match('/\A[a-zA-Z0-9:_-]{1,64}\z/',$operationKey)) throw ValidationException::withMessages(['operation_key'=>__('Invalid import operation key.')]);
        $hash=hash('sha256',hash_file('sha256',$path).json_encode($context,JSON_THROW_ON_ERROR));
        return CatalogueCodes::transaction(function () use ($path,$context,$operationKey,$hash) {
            $existing=DB::table('product_import_runs')->where('operation_key',$operationKey)->lockForUpdate()->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash,$hash)) throw ValidationException::withMessages(['operation_key'=>__('This import key was already used with different data.')]);
                return $existing;
            }
            $preview=$this->preview($path,$context);
            if (!$preview['valid']) throw ValidationException::withMessages(['file'=>__('The entire import is blocked. Download the error report and correct the CSV.')]);
            $id=DB::table('product_import_runs')->insertGetId([
                'point_of_sale_id'=>$context['shop_id'],'user_id'=>$context['user_id'],'operation_key'=>$operationKey,'request_hash'=>$hash,
                'status'=>'applied','line_count'=>$preview['line_count'],'report'=>json_encode(['context'=>$context,'rows'=>$preview['rows']],JSON_THROW_ON_ERROR),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $shop=PointOfSale::findOrFail($context['shop_id']);
            foreach ($preview['rows'] as $entry) $this->createRow($entry['values'],$context,$shop,'import:'.$id.':'.$entry['line']);
            return DB::table('product_import_runs')->find($id);
        });
    }

    private function createRow(array $row, array $context, PointOfSale $shop, string $key): void
    {
        $brand=empty($row['brand']) ? null : Brand::firstOrCreate(['name'=>$row['brand']]);
        $category=empty($row['category']) ? null : Category::firstOrCreate(['name'=>$row['category']]);
        $product=new Product([
            'sku'=>$row['sku'],'name'=>$row['name'],'unit_id'=>$row['unit_id'],'allows_fractional'=>$row['allows_fractional'],
            'brand_id'=>$brand?->id,'category_id'=>$category?->id,'description'=>$row['description'] ?? null,'quantity'=>0,
            'price'=>(string)BigDecimal::of($row['price'])->toScale(2,RoundingMode::HalfUp),
            'purchase_price'=>(string)BigDecimal::of($row['purchase_price'])->toScale(2,RoundingMode::HalfUp),
            'discount'=>(string)BigDecimal::of($row['discount'])->toScale(2,RoundingMode::HalfUp),'discount_type'=>$row['discount_type'],'status'=>$row['status']==='1',
        ]);
        $slugBase=mb_substr(\Illuminate\Support\Str::slug($row['name']) ?: 'product',0,240);
        $slug=$slugBase; $suffix=0;
        while (Product::where('slug',$slug)->exists()) $slug=$slugBase.'-'.++$suffix;
        $product->setAttribute('slug',$slug);
        $product->save();
        $unit=$row['unit_id'] && $row['allows_fractional']!==null
            ? app(ProductUnitService::class)->save($product,['unit_id'=>$row['unit_id'],'code'=>'BASE','label'=>$row['unit'],'factor'=>'1','is_active'=>true])
            : null;
        app(ReferencePricingService::class)->sync($product,$row,true);
        if (!BigDecimal::of($row['quantity'])->isPositive()) return;
        $purchase=app(PurchaseService::class)->create([
            'supplier_id'=>$context['supplier_id'],'date'=>now('Africa/Douala')->toDateString(),'idempotency_key'=>$key,
            'items'=>[['product_id'=>$product->id,'product_unit_id'=>$unit->id,'quantity'=>$row['quantity'],'received_quantity'=>'0','unit_cost'=>$row['purchase_price'],'sale_price'=>$row['price']]],
        ],$shop,$context['user_id']);
        app(PurchaseService::class)->receive($purchase,[[
            'purchase_item_id'=>$purchase->items->first()->id,'quantity'=>$row['quantity'],'unit_cost'=>$row['purchase_price'],
            'expiry_status'=>$row['expiry_status'],'expires_on'=>$row['expire_date'] ?: null,'batch_number'=>$row['batch_number'],
        ]],$shop,$context['user_id'],$key.':receipt');
    }

    private function invalidQuotesLine(string $bytes,string $separator): ?int
    {
        $state='start'; $line=1;
        for ($index=0,$length=strlen($bytes);$index<$length;$index++) {
            $char=$bytes[$index];
            if ($state==='quoted') {
                if ($char==='"') {
                    if ($index+1<$length && $bytes[$index+1]==='"') $index++;
                    else $state='closed';
                }
                if ($char==="\n") $line++;
                continue;
            }
            if ($char===$separator || $char==="\n" || $char==="\r") {
                $state='start'; if ($char==="\n") $line++; continue;
            }
            if ($char==='"') {
                if ($state!=='start') return $line;
                $state='quoted'; continue;
            }
            if ($state==='closed' && $char!==' ' && $char!=="\t") return $line;
            if ($state==='start' && $char!==' ' && $char!=="\t") $state='plain';
        }
        return $state==='quoted' ? $line : null;
    }

    private function failure(int $line,string $field,string $message): array
    {
        return ['valid'=>false,'errors'=>[['line'=>$line,'sku'=>'','field'=>$field,'message'=>$message]],'rows'=>[],'line_count'=>0];
    }
}
