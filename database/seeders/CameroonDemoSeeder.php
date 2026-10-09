<?php

namespace Database\Seeders;

use App\Models\{Category, Customer, PointOfSale, Product, ProductBarcode, ProductStock, Supplier, Unit, User};
use App\Services\StockService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Demonstration only: deliberately refuses every production database. */
final class CameroonDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->runningInConsole() || DB::connection()->getDatabaseName() !== 'qpos_seed_2026_10_09') {
            throw new \RuntimeException('CameroonDemoSeeder requires the isolated qpos_seed_2026_10_09 database.');
        }
        $credentials = [];
        DB::transaction(function () use (&$credentials) {
            \App\Models\Currency::firstOrCreate(['code'=>'XAF'],['name'=>'Franc CFA BEAC','symbol'=>'FCFA']);
            DB::table('currencies')->update(['active'=>false]);
            DB::table('currencies')->where('code','XAF')->update(['active'=>true]);
            $shop = PointOfSale::firstOrCreate(['code'=>'MAIN'], ['name'=>'Boutique démo Douala', 'is_active'=>true]);
            $now = now('Africa/Douala');
            DB::table('reporting_runtime')->insertOrIgnore(['key'=>'demo_seed_date', 'value'=>$now->toDateString()]);
            $day = \Carbon\CarbonImmutable::parse(DB::table('reporting_runtime')->where('key','demo_seed_date')->value('value'), 'Africa/Douala');
            $roles = [
                'Demo Cashier'=>['sale_create','cash_session_manage','sale_view','customer_view','point_of_sale_access'],
                'Demo Seller'=>['sale_create','customer_view','point_of_sale_access'],
                'Demo Stock'=>['product_view','stock_view','stock_adjust','stock_inventory','point_of_sale_access'],
            ];
            foreach ($roles as $name=>$permissions) {
                // Definitions affect only this guarded demo database.
                $role=Role::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
                $available=DB::table('permissions')->whereIn('name',$permissions)->pluck('name')->all();
                $role->syncPermissions($available);
            }
            foreach ([['Admin','admin','Admin'],['Marie','marie','Demo Cashier'],['Pauline','pauline','Demo Cashier'],['Jean','jean','Demo Seller'],['Pierre','pierre','Demo Stock']] as [$name,$login,$role]) {
                $email=$login.'@qpos.test';$user=User::where('email',$email)->first();
                if (!$user) { $password=Str::password(24);$user=User::create(['name'=>$name,'username'=>'demo-'.$login,'email'=>$email,'password'=>Hash::make($password),'is_suspended'=>false]);$credentials[$email]=$password; }
                $user->assignRole($role);$user->pointOfSales()->syncWithoutDetaching([$shop->id=>['is_active'=>true]]);
                DB::table('users')->where('id',$user->id)->update(['preferred_point_of_sale_id'=>$shop->id]);
            }
            $admin=User::where('email','admin@qpos.test')->firstOrFail();
            foreach (['Ciment/Materiaux','Outils','Quincaillerie','Peinture','Plomberie','Electricite','Alimentation','Boissons','Hygiene','Emballages'] as $name) {
                Category::firstOrCreate(['name'=>$name]);
            }
            DB::table('categories')->whereIn('name',['Alimentation','Boissons'])->update(['expiry_policy'=>'perishable','expiry_months'=>12]);
            foreach (['Ets Fotso','Quincaillerie Mfoundi','SOCIMAT','Camlait','SABC','AZUR','Ets Kamdem','ChoCoCam','Import Afrique','Grossiste Mokolo'] as $name) Supplier::firstOrCreate(['name'=>$name],['is_active'=>true,'is_internal'=>false]);
            foreach (['Ngono Jean','Abena Marie','Fotso Paul','Biya Rose','Kamdem Pierre','Ngo Bassong','Tchoua Paul','Manga Rose','Bamenda Construction','Douala Batiment','Quincaillerie Bonaberi','Restaurant Le Wouri','Hotel La Falaise','Boulangerie Quartier','Ets Nkoulou'] as $name) Customer::firstOrCreate(['name'=>$name],['is_active'=>true]);
            $walking=Customer::firstOrCreate(['name'=>'Walking Customer'],['is_active'=>true]);DB::table('customers')->where('id',$walking->id)->update(['internal_code'=>'walking']);
            $units=[];
            foreach(['piece'=>'Pièce','kg'=>'Kilogramme','L'=>'Litre','sac'=>'Sac','palette'=>'Palette','carton'=>'Carton','sachet'=>'Sachet','bidon'=>'Bidon','bouteille'=>'Bouteille','casier'=>'Casier'] as $code=>$label) $units[$code]=Unit::firstOrCreate(['short_name'=>$code],['title'=>$label,'is_active'=>true])->id;
            foreach ($this->products() as $i=>[$name,$listedPrice,$category,$base,$listedFactor,$group]) {
                $sku=sprintf('DEMO-CM-%03d',$i+1);
                $unitPrice=(string)BigDecimal::of($listedPrice)->dividedBy($listedFactor,6,RoundingMode::HalfUp);
                $product=Product::firstOrCreate(['sku'=>$sku],['name'=>$name,'category_id'=>Category::where('name',$category)->value('id'),'unit_id'=>$units[$base],'allows_fractional'=>in_array($base,['kg','L'],true),'price'=>$listedPrice,'quantity'=>0,'status'=>true,'discount'=>0,'discount_type'=>'fixed']);
                $product->forceFill(['catalogue_price_ttc'=>$unitPrice,'catalogue_reference_cost'=>null,'catalogue_discount'=>'0','catalogue_discount_type'=>'fixed'])->save();
                $packs=match($group){
                    'ciment'=>[['sac','Sac',1],['palette','Palette de 50 sacs',50]],
                    'clous'=>[['kg','kg',1],['sachet','Sachet de 5 kg',5],['carton','Carton de 25 kg',25]],
                    'riz'=>[['kg','kg',1],['sac','Sac 5 kg',5],['sac','Sac 25 kg',25],['sac','Sac 50 kg',50]],
                    'huile'=>[['bouteille','Bouteille 1 L',1],['bidon','Bidon 5 L',5],['carton','Carton de 12 bouteilles',12]],
                    'savon'=>[['piece','Pièce',1],['carton','Carton de 48 pièces',48]],
                    'biere'=>[['bouteille','Bouteille',1],['casier','Casier de 12 bouteilles',12]],
                    default=>[[$base,$base,1]],
                };
                foreach($packs as [$unit,$label,$factor]) {
                    $price=$factor==$listedFactor?(string)$listedPrice:(string)BigDecimal::of($unitPrice)->multipliedBy($factor)->toScale(6,RoundingMode::HalfUp);
                    $pack=$product->productUnits()->firstOrCreate(['code'=>$unit.'-'.$factor],['unit_id'=>$units[$unit],'label'=>$label,'factor'=>(string)$factor,'is_reference'=>$factor===1,'is_active'=>true,'sale_price_ttc'=>$price,'reference_purchase_cost'=>null]);
                    $barcode=sprintf('2609%03d%03d',$i+1,$factor);
                    $pack->barcodes()->where('barcode','<>',$barcode)->update(['is_active'=>false]);
                    $pack->barcodes()->firstOrCreate(['barcode'=>$barcode],['is_active'=>true]);
                }
                // No purchase price was supplied; unknown costs stay unknown.
                $stock=($i>=10&&$i<=14)?$i-5:(($i>=15&&$i<=17)?0:100+($i%9)*25);
                if ($stock===0) { ProductStock::firstOrCreate(['point_of_sale_id'=>$shop->id,'product_id'=>$product->id]);continue; }
                $dated=$category==='Alimentation'||$category==='Boissons';
                $expires=$i===30?$day->addDays(10):($i===33?$day->addDays(20):($i===40?$day->subDay():$day->addMonths(12)));
                app(StockService::class)->increase($shop->id,$product->id,(string)$stock,[
                    'type'=>'receipt','correlation_key'=>'demo-cm-opening-'.$sku,'user_id'=>$admin->id,'reason'=>'Démonstration fictive Session A : stock initial',
                    'batch'=>['batch_number'=>'DEMO-'.$sku,'expiry_status'=>$dated?'dated':'not_applicable','expires_on'=>$dated?$expires->toDateString():null,'received_at'=>$day->toDateTimeString(),'unit_cost'=>null,'currency_code'=>null,'cost_unknown'=>true,'provenance'=>'receipt'],
                ]);
            }
        });
        if($credentials)file_put_contents(sys_get_temp_dir().'/qpos-seed-credentials.txt',json_encode($credentials,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    }

    private function products(): array
    {
        return [
            ['Ciment CIMENCAM 50 kg',5000,'Ciment/Materiaux','sac',1,'ciment'],['Ciment DANGOTE 50 kg',4800,'Ciment/Materiaux','sac',1,'ciment'],['Fer 8 mm',4500,'Ciment/Materiaux','piece',1,''],
            ['Clous 5 cm',1000,'Quincaillerie','kg',1,'clous'],['Clous 8 cm',1200,'Quincaillerie','kg',1,'clous'],['Marteau 500 g',2500,'Outils','piece',1,''],['Tournevis',1500,'Outils','piece',1,''],['Scie',3500,'Outils','piece',1,''],['Pince',2000,'Outils','piece',1,''],
            ['Peinture Seigneurie 5 L',15000,'Peinture','piece',1,''],['Peinture blanche 20 L',45000,'Peinture','piece',1,''],['Vernis 1 L',5000,'Peinture','piece',1,''],['Pinceau',800,'Peinture','piece',1,''],['Rouleau',1500,'Peinture','piece',1,''],['Tuyau PVC',3500,'Plomberie','piece',1,''],['Robinet',5000,'Plomberie','piece',1,''],['Raccord PVC',500,'Plomberie','piece',1,''],['Colle PVC',1500,'Plomberie','piece',1,''],
            ['Fil 2.5 mm',25000,'Electricite','piece',1,''],['Fil 1.5 mm',18000,'Electricite','piece',1,''],['Ampoule LED 12 W',1500,'Electricite','piece',1,''],['Ampoule LED 20 W',2500,'Electricite','piece',1,''],['Interrupteur',800,'Electricite','piece',1,''],['Prise',1000,'Electricite','piece',1,''],['Disjoncteur',3500,'Electricite','piece',1,''],['Serrure',8000,'Quincaillerie','piece',1,''],['Charnière',500,'Quincaillerie','piece',1,''],['Cadenas',3000,'Quincaillerie','piece',1,''],['Vis boîte',1500,'Quincaillerie','piece',1,''],['Cheville boîte',1200,'Quincaillerie','piece',1,''],
            ['Riz 5 kg',3500,'Alimentation','kg',5,'riz'],['Riz 25 kg',16000,'Alimentation','kg',25,'riz'],['Riz 50 kg',30000,'Alimentation','kg',50,'riz'],['Huile 1 L',1200,'Alimentation','L',1,'huile'],['Huile 5 L',5500,'Alimentation','L',5,'huile'],['Sucre 1 kg',700,'Alimentation','kg',1,''],['Sucre 50 kg',32000,'Alimentation','piece',1,''],['Farine 1 kg',600,'Alimentation','kg',1,''],['Farine 50 kg',25000,'Alimentation','piece',1,''],['Sel 500 g',300,'Alimentation','piece',1,''],['Lait poudre 400 g',2500,'Alimentation','piece',1,''],['Lait concentré',1500,'Alimentation','piece',1,''],['Café 100 g',2000,'Alimentation','piece',1,''],['Thé 25 sachets',800,'Alimentation','piece',1,''],['Spaghetti',500,'Alimentation','piece',1,''],['Tomate 400 g',700,'Alimentation','piece',1,''],['Sardines',1000,'Alimentation','piece',1,''],['Maquereau',1200,'Alimentation','piece',1,''],['Biscuits',500,'Alimentation','piece',1,''],['Bonbons',200,'Alimentation','piece',1,''],['Eau 1.5 L pack 6',2000,'Boissons','piece',1,''],['Eau 0.5 L pack 12',2400,'Boissons','piece',1,''],['Coca 1.5 L',1000,'Boissons','piece',1,''],['Fanta 1.5 L',1000,'Boissons','piece',1,''],['Bière 33 Export',8000,'Boissons','bouteille',12,'biere'],
            ['Savon',500,'Hygiene','piece',1,'savon'],['Savon liquide',1500,'Hygiene','piece',1,''],['OMO 1 kg',1500,'Hygiene','piece',1,''],['OMO 5 kg',6500,'Hygiene','piece',1,''],['Papier hygiénique',1000,'Hygiene','piece',1,''],['Dentifrice',800,'Hygiene','piece',1,''],['Brosse à dents',500,'Hygiene','piece',1,''],['Shampoing',2000,'Hygiene','piece',1,''],['Crème Nivea',2500,'Hygiene','piece',1,''],['Couches bébé',5000,'Hygiene','piece',1,''],
        ];
    }
}
