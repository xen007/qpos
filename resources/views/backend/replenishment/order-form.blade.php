@extends('backend.master-tailwind')
@section('title',__('replenishment.orders'))
@section('content')
@include('backend.replenishment.quick-product')
<x-backend.card :title="$po?->reference??__('replenishment.orders')">
<p>{{ __('replenishment.order_help') }}</p>
<form method="post" action="{{ $po?route('backend.admin.purchase-orders.update',$po->id):route('backend.admin.purchase-orders.store') }}" class="space-y-4" id="po-form">@csrf
<input type="hidden" name="operation_key" value="{{ old('operation_key',$key) }}"><input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}">
@if($po)<input type="hidden" name="version" value="{{ $po->version }}"><p>{{ $po->supplier->name }}</p>@else<x-backend.select name="supplier_id" :label="__('Supplier')" :options="$suppliers->pluck('name','id')->all()" required/>@endif
<x-backend.input name="expected_date" :label="__('replenishment.expected_date')" type="date" :value="old('expected_date',$po?->expected_date)"/>
<x-backend.input name="notes" :label="__('Notes')" maxlength="3000" :value="old('notes',$po?->notes)"/>
@if($po)<x-backend.input name="reason" :label="__('Reason')" maxlength="500" required/>@endif
<div class="grid gap-3 md:grid-cols-2"><x-backend.input name="product_search" id="po-search" :label="__('Search products')" maxlength="100"/><x-backend.select name="category_filter" id="po-category" :label="__('Category')" :options="[''=>__('All')]+App\Models\Category::orderBy('name')->pluck('name','id')->all()"/></div>
<div id="po-results" class="flex flex-wrap gap-2" aria-live="polite"></div><div id="po-lines" class="space-y-4"></div>
<p id="po-error" role="alert"></p><p>{{ __('replenishment.estimated_total') }}: <strong id="po-total">—</strong> XAF</p>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button>
</form>
</x-backend.card>
<script type="application/json" id="po-initial">{!! json_encode(old('items',$po?->items->toArray()??[]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
@endsection
@push('script')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const target=document.getElementById('po-lines'),search=document.getElementById('po-search'),results=document.getElementById('po-results'),error=document.getElementById('po-error'),total=document.getElementById('po-total'),category=document.getElementById('po-category');
 let lines=JSON.parse(document.getElementById('po-initial').textContent),seq=0,timer;
 @php($poLabels=['quantity'=>__('Quantity'),'cost'=>__('replenishment.net_cost'),'discount'=>__('Discount'),'packaging'=>__('Packaging'),'remove'=>__('Remove')])
 const labels=@json($poLabels);
 const make=(tag,cls,text)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;};
 function render(){
  target.replaceChildren();let sum=0,known=true;
  lines.forEach((l,i)=>{
   const box=make('fieldset','qpos-card p-3 grid gap-3 md:grid-cols-2');box.append(make('legend','',l.name||l.snapshot?.name||('#'+l.product_id)));
   for(const field of ['product_id','product_unit_id']){const input=make('input');input.type='hidden';input.name='items['+i+']['+field+']';input.value=l[field];box.append(input);}
   box.append(make('p','',l.unit_label||l.snapshot?.unit_label||''));
   for(const [field,label] of [['quantity_ordered',labels.quantity],['unit_price',labels.cost],['discount_amount',labels.discount]]){
    const wrap=make('label','',label),input=make('input','qpos-control');input.type='number';input.min=field==='quantity_ordered'?'0.000001':'0';input.step='0.000001';input.name='items['+i+']['+field+']';input.value=l[field]??(field==='discount_amount'?'0':'');input.required=field==='quantity_ordered';
    input.addEventListener('input',()=>{l[field]=input.value;calculate();});wrap.append(input);box.append(wrap);
   }
   const remove=make('button','qpos-button qpos-button-md qpos-button-danger',labels.remove);remove.type='button';remove.addEventListener('click',()=>{lines.splice(i,1);render();});box.append(remove);target.append(box);
  });calculate();
 }
 function calculate(){let sum=0,known=true;for(const l of lines){if(l.unit_price===null||l.unit_price===undefined||l.unit_price===''){known=false;break;}sum+=Number(l.quantity_ordered||0)*Number(l.unit_price)-Number(l.discount_amount||0);}total.textContent=known?new Intl.NumberFormat(document.documentElement.lang||'fr',{minimumFractionDigits:2,maximumFractionDigits:2}).format(sum):'—';}
 function add(p,u){if(lines.some(l=>Number(l.product_unit_id)===Number(u.id)))return;lines.push({product_id:p.id,product_unit_id:u.id,name:p.name,unit_label:u.label,quantity_ordered:'1',unit_price:u.unit_cost??null,discount_amount:'0'});render();}
 async function find(){const current=++seq;if(!search.value.trim()){results.replaceChildren();return;}try{
  const url=new URL(@json(route('backend.admin.purchase-orders.products')));url.searchParams.set('search',search.value);url.searchParams.set('category_id',category.value);url.searchParams.set('operation_point_of_sale_id',@json($shop->id));
  const r=await fetch(url,{headers:{Accept:'application/json'}});if(!r.ok)throw new Error(@json(__('replenishment.lookup_failed')));const data=await r.json();if(current!==seq)return;results.replaceChildren();
  for(const p of data.data)for(const u of p.product_units||[]){const b=make('button','qpos-button qpos-button-md qpos-button-secondary',p.name+' · '+u.label);b.type='button';b.addEventListener('click',()=>add(p,u));results.append(b);}
 }catch(e){error.textContent=e.message;}}
 search.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(find,250);});category.addEventListener('change',find);
 window.addEventListener('qpos:product-created',e=>{const p=e.detail;for(const u of p.product_units||[])if(u.is_reference){add(p,{...u,unit_cost:u.reference_purchase_cost});break;}});
 render();
});
</script>
@endpush
