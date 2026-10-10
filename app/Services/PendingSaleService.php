<?php
namespace App\Services;

use App\Models\{User,PointOfSale,PosCart,Product,ProductUnit,Order};
use App\Support\{SaleOperation as Op,SaleTransaction};
use Illuminate\Support\Facades\{DB,Gate,Schema};

final class PendingSaleService
{
    private function authorize(User $user, int $shop, string $permission): void
    {
        abort_unless(!$user->is_suspended && $user->getRoleNames()->isNotEmpty() && $user->can($permission),403);
        Gate::forUser($user)->authorize('view',PointOfSale::findOrFail($shop));
        abort_unless(Schema::hasTable('pending_sales'),503);
    }
    private function enabled(int $shop): void { abort_unless(app(ShopWorkflowSettings::class)->get($shop)['pending_sale_enabled'],422,__('Seller to cashier handoff is disabled in this store.')); }
    private function event(int $id, ?int $user, string $kind, ?string $reason=null, array $payload=[]): void
    {
        DB::table('pending_sale_events')->insert(['pending_sale_id'=>$id,'user_id'=>$user,'kind'=>$kind,'reason'=>$reason,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'occurred_at'=>now('UTC')]);
    }
    public function refresh(int $shop): void
    {
        if(!Schema::hasTable('pending_sales')) return;
        DB::table('pending_sales')->where('point_of_sale_id',$shop)->whereIn('state',['pending','taken'])
            ->where(fn($q)=>$q->where('expires_at','<=',now('UTC'))->orWhere('lease_expires_at','<=',now('UTC')))->orderBy('id')->pluck('id')->each(function($id){
                DB::transaction(function()use($id){
                    $p=DB::table('pending_sales')->where('id',$id)->lockForUpdate()->first();
                    if(!in_array($p->state,['pending','taken'],true))return;
                    $expired=$p->expires_at<=now('UTC')->format('Y-m-d H:i:s');
                    if(!$expired && !($p->state==='taken' && $p->lease_expires_at<=now('UTC')->format('Y-m-d H:i:s'))) return;
                    DB::table('pending_sales')->where('id',$id)->update(['state'=>$expired?'expired':'pending','cashier_user_id'=>null,'taken_at'=>null,'lease_expires_at'=>null,'updated_at'=>now('UTC')]);
                    $this->event($id,null,$expired?'expired':'lease_released',null,['previous_cashier'=>$p->cashier_user_id]);
                },3);
            });
    }
    private function fingerprint(array $quote, int $shop): array
    {
        $result=[];
        foreach($quote['carts'] as $line) {
            $q=$line['quote'];unset($q['calculated_at']);
            $result[$q['product_unit_id']]=['quote'=>$q,'stock'=>(string)app(StockService::class)->available($shop,$line['product_id'])];
        }
        ksort($result);return $result;
    }
    public function submit(User $user, int $shop, array $data): object
    {
        $this->authorize($user,$shop,'pending_sale_prepare');
        $this->enabled($shop);
        return SaleTransaction::run(function()use($user,$shop,$data){
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();$key=Op::key($data);$hash=Op::hash($user->id,$shop,$data);
            if($old=DB::table('pending_sales')->where('point_of_sale_id',$shop)->where('submission_key',$key)->first()){Op::replay($old,$hash);return $old;}
            $quote=app(SaleService::class)->quote($user->id,$shop,$data);
            if(!$quote['carts']) Op::fail('cart','The cart is empty.');
            if(count($quote['carts'])>200) Op::fail('cart','Select at most 200 sale lines.');
            if(!hash_equals($quote['quote_hash'],$data['quote_hash'])) Op::fail('quote_hash','Prices or cart changed. Review the new total before checkout.');
            if(\Brick\Math\BigDecimal::of($quote['discount'])->isPositive()&&!$user->can('sale_discount')) Op::fail('order_discount','Manual discount permission is required.');
            $settings=app(ShopWorkflowSettings::class)->get($shop);$now=now('UTC');
            $id=DB::table('pending_sales')->insertGetId(['point_of_sale_id'=>$shop,'seller_user_id'=>$user->id,'customer_id'=>$data['customer_id'],'currency_code'=>'XAF','submission_key'=>$key,'request_hash'=>$hash,'proposed_quote_hash'=>$quote['quote_hash'],'proposed_total'=>$quote['total'],'order_discount'=>$quote['discount'],'state'=>'pending','expires_at'=>$now->copy()->addMinutes($settings['pending_expiry_minutes']),'created_at'=>$now,'updated_at'=>$now]);
            $fingerprint=$this->fingerprint($quote,$shop);
            foreach($quote['carts'] as $line) DB::table('pending_sale_items')->insert(['pending_sale_id'=>$id,'product_id'=>$line['product_id'],'product_unit_id'=>$line['quote']['product_unit_id'],'quantity'=>$line['quantity'],'pricing_snapshot'=>json_encode($fingerprint[$line['quote']['product_unit_id']],JSON_THROW_ON_ERROR)]);
            PosCart::where('user_id',$user->id)->where('point_of_sale_id',$shop)->where('cart_id',$data['cart_id'])->delete();
            $this->event($id,$user->id,'submitted');return DB::table('pending_sales')->find($id);
        });
    }
    public function take(User $user, int $shop, int $id): object
    {
        $this->authorize($user,$shop,'pending_sale_collect');abort_unless($user->can('sale_create')&&$user->can('cash_session_manage'),403);$this->refresh($shop);
        return DB::transaction(function()use($user,$shop,$id){
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();$p=DB::table('pending_sales')->where('id',$id)->where('point_of_sale_id',$shop)->lockForUpdate()->first();abort_unless($p,404);
            $this->enabled($shop);
            if($p->state==='taken'&&(int)$p->cashier_user_id===$user->id)return $p;
            if($p->state!=='pending')Op::fail('state','This pending sale is already taken or unavailable.');
            $lease=app(ShopWorkflowSettings::class)->get($shop)['taken_lease_minutes'];
            DB::table('pending_sales')->where('id',$id)->update(['state'=>'taken','cashier_user_id'=>$user->id,'taken_at'=>now('UTC'),'lease_expires_at'=>now('UTC')->addMinutes($lease),'updated_at'=>now('UTC')]);
            $this->event($id,$user->id,'taken');return DB::table('pending_sales')->find($id);
        },3);
    }
    private function buildQuote(User $user, object $p, bool $lock=false): array
    {
        $items=DB::table('pending_sale_items')->where('pending_sale_id',$p->id)->orderBy('product_id')->orderBy('product_unit_id')->get();
        if($lock){Product::whereIn('id',$items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();ProductUnit::whereIn('id',$items->pluck('product_unit_id'))->orderBy('id')->lockForUpdate()->get();}
        $cart='pending-sale-'.$p->id.'-cashier-'.$user->id;
        PosCart::where('user_id',$user->id)->where('point_of_sale_id',$p->point_of_sale_id)->where('cart_id',$cart)->whereNotIn('product_unit_id',$items->pluck('product_unit_id'))->delete();
        foreach($items as $item) PosCart::updateOrCreate(['user_id'=>$user->id,'point_of_sale_id'=>$p->point_of_sale_id,'cart_id'=>$cart,'product_unit_id'=>$item->product_unit_id],['product_id'=>$item->product_id,'quantity'=>$item->quantity]);
        $q=app(SaleService::class)->quote($user->id,$p->point_of_sale_id,['cart_id'=>$cart,'customer_id'=>$p->customer_id,'order_discount'=>$p->order_discount]);
        $current=$this->fingerprint($q,$p->point_of_sale_id);$original=[];foreach($items as $i)$original[$i->product_unit_id]=json_decode($i->pricing_snapshot,true,512,JSON_THROW_ON_ERROR);ksort($original);
        return $q+['cart_id'=>$cart,'customer_id'=>$p->customer_id,'order_discount'=>$p->order_discount,'proposed_total'=>$p->proposed_total,'changed'=>$current!==$original,'review_hash'=>Op::hash($user->id,$p->point_of_sale_id,[$p->id,$q['quote_hash'],$current])];
    }
    public function quote(User $user,int $shop,int $id): array
    {
        $this->authorize($user,$shop,'pending_sale_collect');$this->refresh($shop);
        return DB::transaction(function()use($user,$shop,$id){$p=DB::table('pending_sales')->where('id',$id)->where('point_of_sale_id',$shop)->lockForUpdate()->first();abort_unless($p,404);$this->enabled($shop);abort_unless($p->state==='taken'&&(int)$p->cashier_user_id===$user->id,403);return $this->buildQuote($user,$p);},3);
    }
    public function complete(User $user,int $shop,int $id,array $data): Order
    {
        $this->authorize($user,$shop,'pending_sale_collect');abort_unless($user->hasAllPermissions(['sale_create','cash_session_manage']),403);$this->refresh($shop);
        return SaleTransaction::run(function()use($user,$shop,$id,$data){
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();$p=DB::table('pending_sales')->where('id',$id)->where('point_of_sale_id',$shop)->lockForUpdate()->first();abort_unless($p,404);$hash=Op::hash($user->id,$shop,$data);$key=Op::key($data);
            $this->enabled($shop);
            if($p->state==='completed'){
                $event=DB::table('pending_sale_events')->where('pending_sale_id',$id)->where('kind','completed')->first();$payload=json_decode($event->payload,true);
                if($p->final_operation_key!==$key||!hash_equals($payload['command_hash'],$hash))Op::fail('operation_key','This operation key was used with different data.');
                return Order::findOrFail($p->completed_order_id);
            }
            abort_unless($p->state==='taken'&&(int)$p->cashier_user_id===$user->id,403);
            $this->authorize(User::findOrFail($p->seller_user_id),$shop,'pending_sale_prepare');
            app(CashService::class)->active($user->id,$shop);$q=$this->buildQuote($user,$p,true);
            if(!hash_equals($q['review_hash'],$data['review_hash']) || ($q['changed']&&empty($data['confirm_changes'])))Op::fail('review_hash','Stock or prices changed. Review and confirm the updated pending sale.');
            $command=collect($data)->except(['review_hash','confirm_changes'])->all();$command['cart_id']=$q['cart_id'];$command['customer_id']=$p->customer_id;$command['order_discount']=$p->order_discount;$command['quote_hash']=$q['quote_hash'];
            $order=app(SaleService::class)->checkout($user->id,$shop,$command);
            $snapshot=$order->checkout_snapshot;
            $snapshot['seller_label']=User::findOrFail($p->seller_user_id)->name;
            $snapshot['pending_sale_id']=$p->id;
            $order->update(['prepared_by_user_id'=>$p->seller_user_id,'checkout_snapshot'=>$snapshot]);
            DB::table('pending_sales')->where('id',$id)->update(['state'=>'completed','completed_order_id'=>$order->id,'final_operation_key'=>$key,'updated_at'=>now('UTC')]);
            $this->event($id,$user->id,'completed',null,['order_id'=>$order->id,'command_hash'=>$hash,'seller_id'=>$p->seller_user_id,'cashier_id'=>$user->id]);return $order;
        });
    }
    public function resolve(User $user,int $shop,int $id,string $action,string $reason): void
    {
        $this->authorize($user,$shop,$action==='release'?'pending_sale_collect':'pending_sale_prepare');$this->refresh($shop);
        if(trim($reason)==='')Op::fail('reason','A reason is required.');
        DB::transaction(function()use($user,$shop,$id,$action,$reason){
            $p=DB::table('pending_sales')->where('id',$id)->where('point_of_sale_id',$shop)->lockForUpdate()->first();abort_unless($p,404);
            if($action==='release'){abort_unless($p->state==='taken'&&(int)$p->cashier_user_id===$user->id,403);$state='pending';}
            else {abort_unless(($p->state==='pending'&&(int)$p->seller_user_id===$user->id)||($user->hasRole('Admin')&&in_array($p->state,['pending','taken'],true)),403);$state='cancelled';}
            DB::table('pending_sales')->where('id',$id)->update(['state'=>$state,'cashier_user_id'=>null,'taken_at'=>null,'lease_expires_at'=>null,'updated_at'=>now('UTC')]);$this->event($id,$user->id,$action==='release'?'returned':'cancelled',trim($reason));
        },3);
    }
}
