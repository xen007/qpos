import React, { useCallback, useEffect, useState } from "react";
import Suppliers from "./Suppliers";
import axios from "axios";
import Swal from "sweetalert2";
import { toast } from "sonner";
import translate from "../../utils/translate";
import {parseDecimal, formatDecimal, lineAmount, purchaseTotals} from "./decimal";

const localToday = () => {
    const parts = Object.fromEntries(new Intl.DateTimeFormat("en-CA",{timeZone:"Africa/Douala",year:"numeric",month:"2-digit",day:"2-digit"}).formatToParts(new Date()).map(part=>[part.type,part.value]));
    return [parts.year,parts.month,parts.day].join("-");
};

import { Search, Package, Plus, Trash2, Check } from "lucide-react";
import { Field, EmptyState, WorkspaceToaster } from "../WorkspaceUI";


export default function Purchase() {
    const [operationKey] = useState(()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),byte=>byte.toString(16).padStart(2,"0")).join(""));

    const [searchTerm, setSearchTerm] = useState("");
    const [barcode, setBarcode] = useState("");
    const [selectedSupplier, setSelectedSupplier] = useState(null);
    const [purchaseId, setPurchaseId] = useState(null);
    const [date, setDate] = useState(localToday);
    const [dueDate, setDueDate] = useState("");
    const [supplierId, setSupplierId] = useState(null);
    const [tax, setTax] = useState("");
    const [discount, setDiscount] = useState("");
    const [shipping, setShipping] = useState("");
    const [products, setProducts] = useState([]);
    const [searchResults, setSearchResults] = useState([]);
    useEffect(() => {
        const searchParams = new URLSearchParams(window.location.search);
        const barcodeParam = searchParams.get("barcode");
        const purchase_id = searchParams.get("purchase_id");
        if (barcodeParam) {
            setSearchTerm(barcodeParam);
            setBarcode(barcodeParam);
        }
        if (purchase_id) {
            setPurchaseId(purchase_id);
        }
    }, []);
    useEffect(() => {
        if (barcode) {
            getProducts();
        }
    }, [barcode]);
    useEffect(() => {
        if (purchaseId) {
            getPurchaseProducts();
        }
    }, [purchaseId]);
    const getPurchaseProducts = useCallback(async () => {
        try {
            const res = await axios.get(`/admin/purchase/${purchaseId}`);
            const purchaseData = res.data;
            const purchaseProducts = purchaseData?.items?.map((item) => ({
                item_id: item.id,
                id: item.product_id,
                name: item.name,
                price: item.price,
                purchase_price: item.purchase_price,
                stock: item.stock,
                qty: item.quantity,
                subTotal: item.purchase_price * item.quantity,
            }));
            setProducts(purchaseProducts);
            setDate(purchaseData?.date ? purchaseData.date.split(" ")[0] : localToday());
            setSelectedSupplier({
                value: purchaseData?.supplier_id,
                label: purchaseData?.supplier?.name,
            });
            setSupplierId(purchaseData?.supplier_id ?? null);
            setTax(purchaseData?.tax ?? "");
            setDiscount(purchaseData?.discount_value ?? "");
            setShipping(purchaseData?.shipping ?? "");
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
        }
    }, [purchaseId]);

    const getProducts = useCallback(async () => {
        if (!searchTerm.trim()) {
            console.log("Search term is empty");
            return;
        }

        try {
            const res = await axios.get("/admin/products", {
                params: { search: searchTerm },
            });
            const product = res.data?.data?.[0];
            if (product) handleProductSelect(product);
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            // Clear searchTerm if needed
            setSearchTerm("");
        }
    }, [searchTerm]); // Don't forget to add searchTerm as a dependency

    // Handle deletion of a product
    const lineKey = product => `${product.id}:${product.product_unit_id}`;
    const handleDelete = (key) => {
        setProducts(products.filter((product) => lineKey(product) !== key));
    };

    // Update quantity and recalculate subtotal
    const handleQtyChange = (key, value) => {
        const updatedProducts = products.map((product) => {
            if (lineKey(product) === key) {
                const newQty = value;
                return {
                    ...product,
                    qty: newQty,
                    received_qty: String(product.received_qty) === String(product.qty) ? newQty : product.received_qty,
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };

    // Update purchase price and recalculate subtotal
    const handlePriceChange = (key, value) => {
        const updatedProducts = products.map((product) => {
            if (lineKey(product) === key) {
                const newPrice = value;
                return {
                    ...product,
                    purchase_price: newPrice,
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };
    const handlePackageChange = (key, unitId) => {
        const line = products.find(item=>lineKey(item) === key);
        if (products.some(item=>lineKey(item)!==key && item.id===line?.id && String(item.product_unit_id)===String(unitId))) {
            toast.error(translate("This packaging is already present.")); return;
        }
        setProducts(current => current.map(line => {
            if (lineKey(line) !== key) return line;
            const unit = line.product_units.find(item => String(item.id) === String(unitId));
            if (!unit) return line;
            const cost = unit.unit_cost ?? (unit.is_reference ? line.reference_cost : "");
            return {...line, product_unit_id: unit.id, unit_label: unit.label, factor: unit.factor,
                purchase_price: String(cost), price: String(unit.sale_price ?? line.price)};
        }));
    };
    const updateLine = (key, fields) => setProducts(current => current.map(line => lineKey(line) === key ? {...line,...fields} : line));
    // Add a new product by searching
    const handleSearchAdd = () => {
        getProducts();
    };

    // Calculate totals with two decimal places
    const calculateTotals = () => {
        return purchaseTotals(products,tax,discount,shipping) || {subTotal:"—",tax:"—",discount:"—",shipping:"—",grandTotal:"—"};
    };

    const totals = calculateTotals();
    const handleSubmit = async () => {
        if (!purchaseTotals(products,tax,discount,shipping) || !products.length) {
            //    toast.error("Total must be greater than zero.");
            return;
        }
        if (!date) {
            toast.error(translate("Please select purchase date."));
            return;
        }
        if (!supplierId) {
            toast.error(translate("Please select a supplier."));
            return;
        }

        // Show confirmation dialog
        Swal.fire({
            title: translate("Are you sure you want to save this purchase?"),
            showDenyButton: true,
            confirmButtonText: translate("Yes"),
            denyButtonText: translate("No"),
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then(async (result) => {
            if (result.isConfirmed) {
                //    console.log("data:", {
                //        products,
                //        supplierId,
                //        totals,
                //    }); return;
                try {
                    const res = await axios.post("/admin/purchase", {
                        idempotency_key:operationKey,
                        date,
                        due_date: dueDate || null,
                        products: products.map(line => ({id:line.id,product_unit_id:line.product_unit_id,qty:String(line.qty),received_qty:String(line.received_qty),purchase_price:String(line.purchase_price),price:String(line.price),expiry_status:line.expiry_status,expires_on:line.expires_on || null})),
                        supplierId,
                        totals: {tax:tax || "0",discount:discount || "0",shipping:shipping || "0"},
                    });
                    setProducts([]);
                    setDate(localToday());
                    setTax("");
                    setDiscount("");
                    setShipping("");
                    toast.success(res?.data?.message);
                    window.location.href = window.qposPurchaseIndex;
                } catch (err) {
                    toast.error(
                        err.response?.data?.message || translate("An error occurred")
                    );
                }
            }
        });
    };

    // product search
    useEffect(() => {
        const controller = new AbortController();
        if (!searchTerm.trim()) {
            setSearchResults([]);
            return;
        }
        // Define the asynchronous function
        async function getProducts() {
            if (!searchTerm.trim()) {
                setSearchResults([]);
                return;
            }

            try {
                const res = await axios.get("/admin/products", {
                    params: { search: searchTerm },
                    signal: controller.signal,
                });

                const productsData = res.data;
                setSearchResults(productsData?.data || []);
            } catch (error) {
                if (axios.isCancel(error)) return;
                console.error("Error fetching products:", error);
            }
        }
        // Call the async function inside useEffect
        const timer = setTimeout(getProducts, 250);
        return () => { clearTimeout(timer); controller.abort(); };
    }, [searchTerm]);
    // Handle adding selected product to the products list
    // Handle adding selected product to the products list
    const handleProductSelect = (product) => {
        const units = product.product_units || [];
        const unit = units.find(item => item.is_reference) || units[0];
        if (!unit) { toast.error(translate("Configure an active purchase packaging first.")); return; }
        const cost = unit.unit_cost ?? product.purchase_price;
        const next = {id: product.id, name: product.name, price: String(unit.sale_price ?? product.price),
            purchase_price: String(cost), reference_cost: String(product.purchase_price), product_units: units, product_unit_id: unit.id,
            unit_label: unit.label, factor: unit.factor, stock: product.quantity,
            qty: "1", received_qty: "1", expiry_status: "unknown", expires_on: ""};
        setProducts(current => {
            const existing = current.findIndex(line=>lineKey(line)===lineKey(next));
            return existing < 0 ? [...current,next] : current.map((line,index) => {
                if (index!==existing) return line;
                const qty=parseDecimal(line.qty), received=parseDecimal(line.received_qty);
                return qty===null || received===null ? line : {...line,qty:formatDecimal(qty+1000000n),received_qty:formatDecimal(received+1000000n)};
            });
        });

        // Clear search term and results
        setSearchTerm("");
        setSearchResults([]);
    };
    return (
        <div className="qpos-purchase-workspace">
            <section className="qpos-card">
                <header className="qpos-workspace-heading"><h2>{translate(purchaseId ? "Edit Purchase" : "Purchase Create")}</h2><span className="qpos-badge qpos-badge-info">XAF</span><Package size={24} aria-hidden="true" /></header>
                <div className="qpos-workspace-body qpos-search-grid">
                    <Field label={translate("Purchase Date")}><input id="date" type="date" className="qpos-control" required value={date || ''} onChange={e => setDate(e.target.value || null)} /></Field>
                    <Field label={translate("Due date")}><input id="due-date" type="date" className="qpos-control" value={dueDate} onChange={e => setDueDate(e.target.value)} /></Field>
                    <div><label className="qpos-field-label" htmlFor="purchase-supplier">{translate("Supplier")}</label><Suppliers setSupplierId={setSupplierId} oldSupplier={selectedSupplier} /></div>
                </div>
            </section>
            <section className="qpos-card">
                <header className="qpos-workspace-heading"><h2>{translate("Purchase items")}</h2><span className="qpos-badge qpos-badge-info">{products.length}</span></header>
                <div className="qpos-workspace-body">
                    <form className="qpos-purchase-search" onSubmit={e => { e.preventDefault(); handleSearchAdd(); }}><Field label={translate("Search products")}><span className="qpos-input-icon"><Search size={18} aria-hidden="true" /><input type="search" className="qpos-control" placeholder={translate("Enter product barcode/name")} value={searchTerm} onChange={e => setSearchTerm(e.target.value)} /></span></Field><button className="qpos-button qpos-button-md qpos-button-primary" type="submit"><Plus size={18} aria-hidden="true" />{translate("Add Product")}</button></form>
                    {!!searchResults.length && <ul className="qpos-search-results" aria-label={translate("Search products")}>{searchResults.map(product => <li key={product.id}><button type="button" onClick={() => handleProductSelect(product)}><span>{product.name}</span><strong>{product.price}</strong></button></li>)}</ul>}
                </div>
                {!products.length ? <EmptyState title={translate("Add products to your purchase")} description={translate("Enter product barcode/name")} /> : <div className="qpos-table-scroll" tabIndex="0" role="region" aria-label={translate("Purchase items")}><table className="qpos-table qpos-purchase-table"><thead><tr><th>{translate("Line")}</th><th>{translate("Product Name")}</th><th>{translate("Packaging")}</th><th>{translate("Purchase Price")}</th><th>{translate("Ordered / receive now")}</th><th>{translate("Expiry")}</th><th>{translate("Sub Total")}</th><th>{translate("Action")}</th></tr></thead><tbody>{products.map((product,index) => { const key=lineKey(product); return <tr key={key}><td>{index+1}</td><td><strong>{product.name}</strong></td><td data-label={translate("Packaging")}><select className="qpos-control" aria-label={translate("Packaging") + ': ' + product.name} value={product.product_unit_id} onChange={e => handlePackageChange(key,e.target.value)}>{product.product_units.map(unit => <option key={unit.id} value={unit.id}>{unit.label} × {unit.factor}</option>)}</select></td><td data-label={translate("Purchase Price")}><input type="number" min="0" step="any" className="qpos-control" value={product.purchase_price} onChange={e => handlePriceChange(key,e.target.value)} aria-label={translate("Purchase Price") + ': ' + product.name}/></td><td><label>{translate("Ordered")}</label><input type="number" min="0.000001" step="any" className="qpos-control" aria-label={translate("Ordered") + ': ' + product.name} value={product.qty} onChange={e => handleQtyChange(key,e.target.value)}/><label>{translate("Receive now")}</label><input type="number" min="0" step="any" className="qpos-control" aria-label={translate("Receive now") + ': ' + product.name} value={product.received_qty} onChange={e => updateLine(key,{received_qty:e.target.value})}/></td><td data-label={translate("Expiry")}><select className="qpos-control" aria-label={translate("Expiry") + ': ' + product.name} value={product.expiry_status} onChange={e => updateLine(key,{expiry_status:e.target.value,expires_on:""})}><option value="unknown">{translate("Unknown (blocked)")}</option><option value="dated">{translate("Dated")}</option><option value="not_applicable">{translate("Not applicable")}</option></select>{product.expiry_status==="dated" && <input type="date" className="qpos-control" aria-label={translate("Dated") + ': ' + product.name} value={product.expires_on} onChange={e => updateLine(key,{expires_on:e.target.value})}/>}</td><td data-label={translate("Sub Total")}>{lineAmount(product) === null ? "—" : formatDecimal(lineAmount(product))}</td><td><button type="button" className="qpos-icon-button qpos-icon-button-danger" aria-label={translate("Delete") + ': ' + product.name} onClick={() => handleDelete(key)}><Trash2 size={18} aria-hidden="true" /></button></td></tr>;})}</tbody></table></div>}
            </section>
            <div className="qpos-search-grid">
                <section className="qpos-card"><header className="qpos-workspace-heading"><h2>{translate("Information")}</h2></header><div className="qpos-workspace-body qpos-field-stack"><Field label={translate("Tax")}><input type="number" className="qpos-control" min="0" value={tax} onChange={e => setTax(e.target.value)} /></Field><Field label={translate("Discount")}><input type="number" className="qpos-control" min="0" value={discount} onChange={e => setDiscount(e.target.value)} /></Field><Field label={translate("Shipping")}><input type="number" className="qpos-control" min="0" value={shipping} onChange={e => setShipping(e.target.value)} /></Field></div></section>
                <section className="qpos-card"><header className="qpos-workspace-heading"><h2>{translate("Order summary")}</h2></header><div className="qpos-totals">{[['Subtotal:',totals.subTotal],['Tax:',totals.tax],['Discount:',totals.discount],['Shipping:',totals.shipping]].map(([label,value]) => <div key={label}><span>{translate(label)}</span><strong>{value}</strong></div>)}<div className="qpos-total-highlight"><span>{translate("Grand Total:")}</span><strong>{totals.grandTotal}</strong></div></div><div className="qpos-workspace-footer"><button type="button" className="qpos-button qpos-button-lg qpos-button-primary" disabled={!products.length || !purchaseTotals(products,tax,discount,shipping) || totals.grandTotal.startsWith("-")} onClick={handleSubmit}><Check size={20} aria-hidden="true" />{translate(purchaseId ? "Update" : "Create")}</button></div></section>
            </div>
            <WorkspaceToaster />
        </div>
    );
}
