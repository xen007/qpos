import React, {useState} from "react";
import axios from "axios";
import {toast} from "sonner";
import {Minus,Plus,Trash2} from "lucide-react";
import {EmptyState} from "./WorkspaceUI";
import translate from "../utils/translate";
import getErrorMessage from "../utils/getErrorMessage";
function Row({item,cartId,disabled,refresh,onBusy}) {
    const [quantity,setQuantity]=useState(item.quantity);
    async function send(action,data={}) {onBusy(true);try{await axios.put("/admin/cart/"+action,{cart_id:cartId,id:item.id,...data});refresh();}catch(e){toast.error(getErrorMessage(e));setQuantity(item.quantity);}finally{onBusy(false);}}
    return <article className="qpos-cart-item"><div className="qpos-cart-item-heading"><strong>{item.quote.product_label}</strong><button type="button" className="qpos-icon-button" disabled={disabled} aria-label={translate("Remove item")} onClick={()=>send("delete")}><Trash2 size={18}/></button></div>
    <div className="qpos-cart-item-bottom"><div className="qpos-stepper"><button type="button" disabled={disabled} aria-label={translate("Decrease quantity")} onClick={()=>send("decrement")}><Minus size={16}/></button><input aria-label={translate("Quantity")} className="qpos-control" type="number" min="0.000001" step="0.000001" disabled={disabled} value={quantity} onChange={e=>setQuantity(e.target.value)} onBlur={()=>{if(quantity!==item.quantity)send("quantity",{quantity});}} onKeyDown={e=>{if(e.key==="Enter")e.currentTarget.blur();}}/><button type="button" disabled={disabled} aria-label={translate("Increase quantity")} onClick={()=>send("increment")}><Plus size={16}/></button></div><span>{item.quote.price_ttc} XAF</span><strong>{item.row_total} XAF</strong></div>
    {(item.quote.price_rule_id||item.quote.promotion_id)&&<small>{translate("Applied pricing")}: {item.quote.price_rule_id?item.quote.price_ttc+" XAF":""} {item.quote.promotion_id?"−"+item.quote.discount_total+" XAF":""}</small>}</article>;
}
export default function Cart({carts,cartId,disabled,refresh,onBusy}) {return <section className="qpos-cart-items" aria-label={translate("Cart")}>{!carts.length&&<EmptyState title={translate("Cart is empty")}/>}
{carts.map(item=><Row key={item.id+":"+item.quantity} item={item} cartId={cartId} disabled={disabled} refresh={refresh} onBusy={onBusy}/>)}</section>;}
