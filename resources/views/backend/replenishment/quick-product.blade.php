@can('product_create')
<div class="my-4">
<button type="button" class="qpos-button qpos-button-md qpos-button-secondary" id="quick-product-open">{{ __('replenishment.quick_product') }}</button>
<dialog id="quick-product-dialog" class="qpos-card w-full max-w-xl p-4 rounded-lg" aria-labelledby="quick-product-title">
<h2 id="quick-product-title">{{ __('replenishment.quick_product') }}</h2>
<form id="quick-product-form" class="grid gap-3 mt-3">@csrf
<x-backend.input name="name" :label="__('Name')" required maxlength="255"/>
<x-backend.select name="category_id" :label="__('Category')" :options="[''=>__('None')]+App\Models\Category::where('status',true)->pluck('name','id')->all()"/>
<x-backend.select name="unit_id" :label="__('Base unit')" :options="App\Models\Unit::where('is_active',true)->pluck('title','id')->all()" required/>
<x-backend.select name="allows_fractional" :label="__('replenishment.fractional')" :options="['0'=>__('No'),'1'=>__('Yes')]" required/>
<x-backend.input name="price" :label="__('Sale price').' XAF'" type="number" min="0" step="0.000001" required/>
<x-backend.input name="sku" :label="__('SKU')" maxlength="255"/>
<p>{{ __('replenishment.zero_stock') }}</p><p id="quick-product-error" role="alert"></p>
<div class="flex flex-wrap gap-3"><button type="submit" class="qpos-button qpos-button-md qpos-button-primary">{{ __('Create') }}</button><button type="button" id="quick-product-cancel" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Cancel') }}</button></div>
</form></dialog>
</div>
@push('script')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const dialog=document.getElementById('quick-product-dialog'),form=document.getElementById('quick-product-form'),error=document.getElementById('quick-product-error');
 @if(request()->routeIs('backend.admin.stock.initial'))
 const savedKey='qpos-startup-draft-{{ auth()->id() }}-{{ $selectedPointOfSale?->id??0 }}',saved=sessionStorage.getItem(savedKey),startup=document.querySelector('form[action="{{ route('backend.admin.stock.initial.store') }}"]');
 if(saved&&startup){try{for(const [k,v] of Object.entries(JSON.parse(saved))){const input=startup.elements.namedItem(k);if(input)input.value=v;}}finally{sessionStorage.removeItem(savedKey);}}
 @endif
 document.getElementById('quick-product-open').addEventListener('click',()=>{error.textContent='';dialog.showModal();});
 document.getElementById('quick-product-cancel').addEventListener('click',()=>dialog.close());
 form.addEventListener('submit',async e=>{
  e.preventDefault();const submit=form.querySelector('[type=submit]');if(submit.disabled)return;submit.disabled=true;error.textContent='';
  try{
   const body=new FormData(form);body.set('status','1');body.set('operation_point_of_sale_id',window.qposOperationShopId||'');
   const response=await fetch(@json(route('backend.admin.replenishment.quick-store')),{method:'POST',body,headers:{Accept:'application/json'}});
   const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message);
   window.dispatchEvent(new CustomEvent('qpos:product-created',{detail:data.product}));
   const target=document.querySelector('[data-quick-product-target]');
   if(target){let option=new Option(data.product.name,data.product.id,true,true);target.add(option);target.dispatchEvent(new Event('change',{bubbles:true}));}
   @if(request()->routeIs('backend.admin.stock.initial'))
   const existing=document.querySelector('form[action="{{ route('backend.admin.stock.initial.store') }}"]');
   const key='qpos-startup-draft-{{ auth()->id() }}-{{ $selectedPointOfSale?->id??0 }}';if(existing){const draft={};for(const [k,v] of new FormData(existing))if(!['_token','operation_key','product_id'].includes(k)&&typeof v==='string')draft[k]=v;sessionStorage.setItem(key,JSON.stringify(draft));}
   const url=new URL(location.href);url.searchParams.set('product_id',data.product.id);location.assign(url.toString());
   @endif
   dialog.close();form.reset();
  }catch(e){error.textContent=e.message||@json(__('replenishment.creation_failed'));}finally{submit.disabled=false;}
 });
});
</script>
@endpush
@endcan
