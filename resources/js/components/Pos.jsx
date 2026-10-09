import React, {useEffect, useRef, useState, useCallback} from "react";
import axios from "axios";
import {toast} from "sonner";
import Swal from "sweetalert2";
import {Barcode, Search, Package, ShoppingCart, Check, Trash2} from "lucide-react";
import {Field, EmptyState, WorkspaceToaster} from "./WorkspaceUI";
import Cart from "./Cart";
import translate from "../utils/translate";
import getErrorMessage from "../utils/getErrorMessage";

import {money, quantity, normalizeInput, validXafPayment} from "../utils/pos-format";

const uuid = () => crypto.randomUUID();
const whole = value => validXafPayment(value) ? BigInt(normalizeInput(value || "0").split(".")[0]) : 0n;
const base = path => String(window.qposBaseUrl || "").replace(/\/$/, "") + path;

export default function Pos() {
    const [products,setProducts]=useState([]),[customers,setCustomers]=useState([]),[customerId,setCustomerId]=useState("");
    const [cartId,setCartId]=useState(""),[quote,setQuote]=useState(null),[session,setSession]=useState(null);
    const [search,setSearch]=useState(""),[barcode,setBarcode]=useState(""),[discount,setDiscount]=useState("0");
    const [cash,setCash]=useState("0"),[card,setCard]=useState("0"),[reference,setReference]=useState(""),[credit,setCredit]=useState("0"),[dueDate,setDueDate]=useState("");
    const [page,setPage]=useState(1),[lastPage,setLastPage]=useState(1),[busy,setBusy]=useState(false),[pending,setPending]=useState(null);
    const [exchangePreview,setExchangePreview]=useState(null),[cartBusy,setCartBusy]=useState(false);
    const [panel,setPanel]=useState("catalogue"),[version,setVersion]=useState(0),[exchange,setExchange]=useState(null);
    const cashEdited=useRef(false);
    const [initialized,setInitialized]=useState(false),[initError,setInitError]=useState(false);
    const mutations=useRef(0);const markMutation=useCallback(value=>{mutations.current=Math.max(0,mutations.current+(value?1:-1));setCartBusy(mutations.current>0);},[]);
    const scan=useRef(null),keyPrefix=useRef(""),requestSeq=useRef(0),quoteSeq=useRef(0);
    const refresh=useCallback(()=>{setQuote(null);setVersion(v=>v+1);},[]);
    useEffect(()=> {
        let live=true;const pinned=new URL(window.location.href);if(window.qposOperationShopId){pinned.searchParams.set("operation_point_of_sale_id",window.qposOperationShopId);window.history.replaceState(null,"",pinned.toString());}
        Promise.all([axios.get("/admin/cash/state"),axios.get("/admin/get/customers")]).then(([state,list])=>{
            if(!live)return;
            setSession(state.data.session);setInitialized(true);
            keyPrefix.current="qpos-pos:"+state.data.user_id+":"+window.qposOperationShopId+":";
            let id=sessionStorage.getItem(keyPrefix.current+"cart")||uuid();sessionStorage.setItem(keyPrefix.current+"cart",id);setCartId(id);
            const saved=sessionStorage.getItem(keyPrefix.current+"pending");const savedValue=saved?JSON.parse(saved):null;if(savedValue){cashEdited.current=true;setPending(savedValue);setQuote(savedValue.quote_snapshot||null);setDiscount(savedValue.sale.order_discount||"0");setCredit(savedValue.sale.credit_amount||"0");setDueDate(savedValue.sale.due_date||"");setCash(savedValue.sale.payments.filter(p=>p.method==="cash").reduce((sum,p)=>sum+whole(p.amount),0n).toString());setCard(savedValue.sale.payments.filter(p=>p.method==="card").reduce((sum,p)=>sum+whole(p.amount),0n).toString());setReference(savedValue.sale.payments.find(p=>p.method==="card")?.external_reference||"");}
            const x=sessionStorage.getItem("qpos-exchange");let ex=x?JSON.parse(x):null;
            if(ex && ex.shop_id===Number(window.qposOperationShopId))setExchange(ex);else ex=null;
            setCustomers(list.data);const walking=list.data.find(c=>c.internal_code==="walking");
            setCustomerId(String(savedValue?.sale.customer_id||ex?.customer_id||walking?.id||""));
        }).catch(e=>{if(live){setInitError(true);toast.error(getErrorMessage(e));}});
        return()=>{live=false;};
    },[]);
    useEffect(()=>{
        if(!cartId || !window.BroadcastChannel)return;
        const instance=uuid(),channel=new BroadcastChannel(keyPrefix.current+"tabs");
        channel.onmessage=event=>{
            if(event.data.cart!==cartId || event.data.instance===instance)return;
            if(event.data.type==="claim")channel.postMessage({type:"occupied",cart:cartId,instance});
            if(event.data.type==="occupied"){
                const id=uuid();sessionStorage.setItem(keyPrefix.current+"cart",id);sessionStorage.removeItem(keyPrefix.current+"pending");setPending(null);setCartId(id);
            }
        };
        channel.postMessage({type:"claim",cart:cartId,instance});
        return()=>channel.close();
    },[cartId]);
    const getProducts=useCallback(async(term="",next=1,code="")=>{
        const seq=++requestSeq.current;if(code)markMutation(true);
        try{const r=await axios.get("/admin/get/products",{params:{search:term,page:next,barcode:code}});
            if(seq!==requestSeq.current)return;
            setProducts(old=>next===1?r.data.data:[...old,...r.data.data]);setPage(next);setLastPage(r.data.meta.last_page);
            if(code && r.data.data.length===1 && !pending){await axios.post("/admin/cart",{cart_id:cartId,product_unit_id:r.data.data[0].id});refresh();toast.success(translate("Cart updated"));}
            else if(code && !r.data.data.length)toast.error(translate("No products found"));
        }catch(e){toast.error(getErrorMessage(e));}finally{if(code)markMutation(false);}
    },[cartId,pending,refresh,markMutation]);
    useEffect(()=>{if(!cartId)return;const timer=setTimeout(()=>getProducts(search),250);return()=>clearTimeout(timer);},[search,cartId,getProducts]);
    useEffect(()=>{
        if(!cartId || !customerId || pending)return;const seq=++quoteSeq.current;
        const timer=setTimeout(()=>axios.get("/admin/cart",{params:{cart_id:cartId,customer_id:customerId,order_discount:discount||"0"}}).then(r=>{if(seq===quoteSeq.current)setQuote(r.data);}).catch(e=>toast.error(getErrorMessage(e))),200);
        return()=>clearTimeout(timer);
    },[cartId,customerId,discount,version,pending]);
    useEffect(()=>{const handler=e=>{if(e.key==="F2"){e.preventDefault();setPanel("catalogue");window.requestAnimationFrame(()=>scan.current?.focus());}if(e.key==="F4"){e.preventDefault();setPanel("checkout");}};window.addEventListener("keydown",handler);return()=>window.removeEventListener("keydown",handler);},[]);
        useEffect(()=>{if(!exchange)return;axios.post('/admin/sales/'+exchange.order_id+'/exchange-preview',{items:exchange.items}).then(r=>setExchangePreview(r.data)).catch(e=>toast.error(getErrorMessage(e)));},[exchange]);
    const add=async id=>{if(busy||pending)return;markMutation(true);try{await axios.post("/admin/cart",{cart_id:cartId,product_unit_id:id});refresh();scan.current?.focus();}catch(e){toast.error(getErrorMessage(e));}finally{markMutation(false);}};
    const total=whole(quote?.total),exchangeAvailable=whole(exchangePreview?.settled_amount),remainingAfterCredit=total>whole(credit)?total-whole(credit):0n,exchangeOffset=exchangeAvailable<remainingAfterCredit?exchangeAvailable:remainingAfterCredit,tender=whole(cash)+whole(card)+whole(credit)+exchangeOffset,due=total>tender?total-tender:0n,change=tender>total?tender-total:0n;
    const paymentInvalid = !validXafPayment(cash) || !validXafPayment(card) || !validXafPayment(credit);
    useEffect(()=>{
        if(!quote || pending || cashEdited.current)return;
        const offset=whole(card)+whole(credit)+exchangeOffset;
        setCash((total>offset?total-offset:0n).toString());
    },[quote,total,card,credit,exchangeOffset,pending]);
    const blockedReason = initError ? "Unable to load the POS. Reload or check your access." : !initialized ? "Loading workspace" : busy ? "Recording sale..." : cartBusy ? "Updating cart..." : !pending && !session ? "Open your cash session" : !pending && !quote ? "Loading cart..." : !pending && !quote.carts.length ? "Cart is empty" : !pending && exchange && !exchangePreview ? "Loading exchange..." : !pending && paymentInvalid ? "XAF payment amounts must be whole francs." : "";
    const savePending=value=>{setPending(value);if(value)sessionStorage.setItem(keyPrefix.current+"pending",JSON.stringify(value));else sessionStorage.removeItem(keyPrefix.current+"pending");};
    async function submit(value){
        setBusy(true);let succeeded=false;
        try{
            let response;
            for(let attempt=0;attempt<2;attempt++){
                try{response=value.exchange?await axios.post("/admin/sales/"+value.exchange.order_id+"/corrections",{...value.exchange,exchange_sale:value.sale},{timeout:20000}):await axios.put("/admin/order/create",value.sale,{timeout:20000});break;}
                catch(e){if(e.response || attempt===1)throw e;}
            }
            succeeded=true;savePending(null);sessionStorage.removeItem("qpos-exchange");
            const id=response.data.order?.id;
            window.location.href=base(id?"/admin/orders/pos-invoice/"+id:"/admin/sale-corrections/"+response.data.correction.id);
        }catch(e){
            if(e.response){
                if(e.response.status===419 || e.response.status>=500){toast.error(translate("Checkout outcome uncertain. Retry the same operation."));return;}
                if(e.response.data?.errors?.expired_confirmation_required){
                    const confirm=await Swal.fire({title:translate("Expired product"),input:"text",inputLabel:translate("Reason"),showCancelButton:true,inputValidator:v=>!v?.trim()?translate("A reason is required"):undefined});
                    if(confirm.isConfirmed){const next={...value,sale:{...value.sale,confirm_expired_sale:true,expired_sale_reason:confirm.value.trim()}};savePending(next);setBusy(false);return submit(next);}
                }
                savePending(null);refresh();
            }
            toast.error(e.response?getErrorMessage(e):translate("Checkout outcome uncertain. Retry the same operation."));
        }finally{if(!succeeded)setBusy(false);}
    }
    async function checkout(){
        if(pending)return submit(pending);
        if(!quote?.carts.length || !session || cartBusy || paymentInvalid || (exchange && !exchangePreview))return;
        const answer=await Swal.fire({title:translate("Confirm checkout"),text:money(quote.total)+" XAF · "+translate("Due")+": "+money(due.toString())+" · "+translate("Change")+": "+money(change.toString()),showCancelButton:true,confirmButtonText:translate("Confirm sale"),cancelButtonText:translate("Cancel")});
        if(!answer.isConfirmed)return;
        const payments=[];if(whole(cash)>0n)payments.push({method:"cash",amount:cash});if(whole(card)>0n)payments.push({method:"card",amount:card,external_reference:reference});
        const sale={operation_key:uuid(),cart_id:cartId,quote_hash:quote.quote_hash,customer_id:Number(customerId),order_discount:discount||"0",credit_amount:credit||"0",payments,due_date:dueDate||null};
        const value={sale,quote_snapshot:quote,exchange:exchange?{...exchange,return_quote_hash:exchangePreview.return_quote_hash}:null};savePending(value);return submit(value);
    }
    return <div className="qpos-pos-grid qpos-touch-workspace">
        {blockedReason&&<div className="qpos-pos-status" role="status" aria-live="polite"><span>{translate(blockedReason)}</span>{initialized&&!session&&window.qposCanManageCash&&<a className="qpos-button qpos-button-md qpos-button-primary" href={base("/admin/cash?operation_point_of_sale_id="+window.qposOperationShopId)}>{translate("Open my session")}</a>}{initialized&&!session&&!window.qposCanManageCash&&<span>{translate("Ask an authorized cashier to open their session.")}</span>}</div>}

        <nav className="qpos-workspace-tabs" role="tablist" aria-label={translate("POS")}><button id="pos-tab-catalogue" type="button" role="tab" aria-selected={panel==="catalogue"} aria-controls="pos-panel-catalogue" onClick={()=>setPanel("catalogue")}><Package size={18}/>{translate("Products")} F2</button><button id="pos-tab-checkout" type="button" role="tab" aria-selected={panel==="checkout"} aria-controls="pos-panel-checkout" onClick={()=>setPanel("checkout")}><ShoppingCart size={18}/>{translate("Cart")} F4</button></nav>
        <section id="pos-panel-catalogue" role="tabpanel" aria-labelledby="pos-tab-catalogue" className={"qpos-card qpos-catalogue-panel "+(panel==="catalogue"?"is-active":"")}>
            <header className="qpos-workspace-heading"><h2>{translate("Product catalogue")}</h2></header>
            <div className="qpos-search-grid">
                <Field label={translate("Enter Product Barcode")}><input ref={scan} className="qpos-control" value={barcode} disabled={busy||!!pending} onChange={e=>setBarcode(e.target.value)} onKeyDown={e=>{if(e.key==="Enter"&&barcode.trim()){e.preventDefault();getProducts("",1,barcode.trim());setBarcode("");}}}/></Field>
                <Field label={translate("Search products")}><input className="qpos-control" type="search" value={search} onChange={e=>setSearch(e.target.value)}/></Field>
            </div>
            <div className="qpos-products-grid">{products.map(p=><button type="button" className="qpos-product" key={p.id} disabled={busy||!!pending} onClick={()=>add(p.id)}><img src={window.qposStorageUrl+"/"+p.image} alt="" loading="lazy" onError={e=>{e.target.onerror=null;e.target.src=window.qposFallbackImage;}}/><span className="qpos-product-name">{p.name}</span><span>{translate("Stock in base units")}: {quantity(p.quantity)} · ×{quantity(p.factor)}</span><strong>{money(p.price)} XAF</strong>{Number(p.expired_quantity)>0&&<small>{translate("Expired stock may require confirmation")}: {quantity(p.expired_quantity)}</small>}</button>)}</div>
            {!products.length&&<EmptyState title={translate("No products found")}/>}
            {page<lastPage&&<button type="button" className="qpos-button qpos-button-md qpos-button-secondary" onClick={()=>getProducts(search,page+1)}>{translate("Load more products")}</button>}
        </section>
        <section id="pos-panel-checkout" role="tabpanel" aria-labelledby="pos-tab-checkout" className={"qpos-card qpos-checkout-panel "+(panel==="checkout"?"is-active":"")}>
            <header className="qpos-workspace-heading"><h2>{translate("Order summary")}</h2></header>
            <div className="qpos-workspace-body">
                {session&&<p>{translate("Cash session")} #{session.id}</p>}
                {exchange&&<p>{translate("Exchange sale")} #{exchange.order_id} · {translate("Return and new sale will be recorded together.")}</p>}
                <Field label={translate("Customer")}><select className="qpos-control" disabled={busy||!!pending||!!exchange} value={customerId} onChange={e=>setCustomerId(e.target.value)}><option value="">{translate("Select customer")}</option>{customers.map(c=><option key={c.id} value={c.id}>{c.internal_code==="walking"?translate("Walking Customer"):c.name}</option>)}</select></Field>
                <button type="button" className="qpos-button qpos-button-sm qpos-button-secondary" disabled={busy||!!pending} onClick={async()=>{const answer=await Swal.fire({title:translate("Create customer"),input:"text",showCancelButton:true});if(answer.isConfirmed&&answer.value?.trim()){try{const r=await axios.post("/admin/create/customers",{name:answer.value.trim()});setCustomers(old=>[...old,r.data]);setCustomerId(String(r.data.id));}catch(e){toast.error(getErrorMessage(e));}}}}>{translate("Create customer")}</button>
            </div>
            <Cart carts={quote?.carts||[]} cartId={cartId} disabled={busy||!!pending} refresh={refresh} onBusy={markMutation}/>
            <div className="qpos-totals">
                <details className="qpos-pos-advanced"><summary>{translate("Advanced options")}</summary><Field label={translate("Manual discount")}><input className="qpos-control" type="number" min="0" step="0.000001" value={discount} disabled={busy||!!pending} onChange={e=>setDiscount(e.target.value)}/></Field></details>
                <div className="qpos-total-highlight"><span>{translate("Total")}</span><strong>{money(quote?.total||"0")} XAF</strong></div>
                {exchange&&<p>{translate("Exchange settlement")}: {money(exchangeOffset.toString())} XAF</p>}
                {quote?.walking&&<p>{translate("Cash only: no debt or credit.")}</p>}
                <Field label={translate("Cash tendered")}><input className="qpos-control" type="number" min="0" step="0.01" value={cash} disabled={busy||!!pending} onChange={e=>{cashEdited.current=true;setCash(e.target.value);}}/></Field>
                <Field label={translate("External card")}><input className="qpos-control" type="number" min="0" step="0.01" value={card} disabled={busy||!!pending} onChange={e=>setCard(e.target.value)}/></Field>
                {whole(card)>0n&&<Field label={translate("External reference")}><input className="qpos-control" maxLength={128} value={reference} disabled={busy||!!pending} onChange={e=>setReference(e.target.value)}/></Field>}
                {!quote?.walking&&<Field label={translate("Use store credit")}><input className="qpos-control" type="number" min="0" step="0.01" value={credit} disabled={busy||!!pending} onChange={e=>setCredit(e.target.value)}/></Field>}
                <div><span>{translate("Due")}</span><strong>{money(due.toString())} XAF</strong></div>
                {due>0n&&!quote?.walking&&<Field label={translate("Due date")}><input className="qpos-control" type="date" value={dueDate} disabled={busy||!!pending} onChange={e=>setDueDate(e.target.value)}/></Field>}
                <div><span>{translate("Change")}</span><strong>{money(change.toString())} XAF</strong></div>
                {busy&&<p role="status">{translate("Recording sale...")}</p>}{pending&&!busy&&<p role="alert">{translate("Checkout outcome uncertain. Retry the same operation.")}</p>}
            </div>
            <div className="qpos-checkout-actions"><button type="button" className="qpos-button qpos-button-md qpos-button-danger" disabled={busy||!!pending} onClick={async()=>{try{await axios.put("/admin/cart/empty",{cart_id:cartId});refresh();}catch(e){toast.error(getErrorMessage(e));}}}><Trash2 size={18}/>{translate("Clear Cart")}</button><button type="button" className="qpos-button qpos-button-lg qpos-button-primary" disabled={!!blockedReason} onClick={checkout}><Check size={20}/>{pending?translate("Retry checkout"):translate("Checkout")}</button></div>
        </section><WorkspaceToaster/>
    </div>;
}
