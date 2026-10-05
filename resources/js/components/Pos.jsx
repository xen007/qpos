import React, {useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import Cart from "./Cart";
import { toast } from "sonner";
import CustomerSelect from "./CutomerSelect";

import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import getErrorMessage from "../utils/getErrorMessage";
import playSound from "../utils/playSound";
import translate from "../utils/translate";

import { Barcode, Search, Package, ShoppingCart, Trash2, Check } from "lucide-react";
import { Field, EmptyState, WorkspaceToaster } from "./WorkspaceUI";

export default function Pos() {

    const [products, setProducts] = useState([]);
    const [activePanel, setActivePanel] = useState('catalogue');
    const [carts, setCarts] = useState([]);
    const [orderDiscount, setOrderDiscount] = useState(0);
    const [paid, setPaid] = useState(0);
    const [due, setDue] = useState(0);
    const [change, setChange] = useState(0);
    const [total, setTotal] = useState(0);
    const [updateTotal, setUpdateTotal] = useState(0);
    const [customerId, setCustomerId] = useState();
    const [cartUpdated, setCartUpdated] = useState(false);
    const [productUpdated, setProductUpdated] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [searchBarcode, setSearchBarcode] = useState("");
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(0);
    const [loading, setLoading] = useState(true);
    const productRequest = useRef(0);
    const barcodeInput = useRef(null);

    useEffect(() => {
        if (activePanel !== 'catalogue') return;
        const frame = window.requestAnimationFrame(() => barcodeInput.current?.focus());
        return () => window.cancelAnimationFrame(frame);
    }, [activePanel]);

    const getProducts = useCallback(
        async (search = "", page = 1, barcode = "") => {
            const requestId = ++productRequest.current;
            setLoading(true);
            try {
                const res = await axios.get('/admin/get/products', {
                    params: { search, page, barcode },
                });
                const productsData = res.data;
                if (requestId !== productRequest.current) return;
                setProducts(prev => page === 1 ? productsData.data : [...prev, ...productsData.data]);
                setCurrentPage(page);
                if (productsData.scan_message) {
                    toast.error(productsData.scan_message);
                    setTotalPages(1);
                    return;
                }
                if (productsData.data.length === 1 && barcode != "") {
                    addProductToCart(productsData.data[0].id);
                }
                setTotalPages(productsData.meta.last_page); // Get total pages
            } catch (error) {
                if (requestId === productRequest.current) toast.error(getErrorMessage(error));
            } finally {
                if (requestId === productRequest.current) setLoading(false);
            }
        },
        []
    );
    useEffect(() => {
        ++productRequest.current;
        const timer = setTimeout(() => getProducts(searchQuery, 1), 250);
        return () => clearTimeout(timer);
    }, [searchQuery, productUpdated, getProducts]);

    const getCarts = async () => {
        try {
            const res = await axios.get('/admin/cart');
            const data = res.data;
            setTotal(data?.total);
            setUpdateTotal(data?.total - orderDiscount);
            setCarts(data?.carts);
        } catch (error) {
            console.error("Error fetching carts:", error);
        }
    };

    useEffect(() => {
        getCarts();
    }, [cartUpdated]);

    useEffect(() => {
        let paid1 = paid;
        let disc = orderDiscount;
        if (paid == "") {
            paid1 = 0;
        }
        if (orderDiscount == "") {
            disc = 0;
        }
        const updatedTotalAmount = parseFloat(total) - parseFloat(disc);
        // Positive balance => still owed (due); negative => overpaid (change to return).
        const balance = updatedTotalAmount - parseFloat(paid1);
        setUpdateTotal(updatedTotalAmount?.toFixed(2));
        setDue((balance > 0 ? balance : 0).toFixed(2));
        setChange((balance < 0 ? -balance : 0).toFixed(2));
    }, [orderDiscount, paid, total]);
    async function addProductToCart(id) {
        try {
            const res = await axios.post("/admin/cart", { id });
            setCartUpdated(previous => !previous);
            playSound(SuccessSound);
            toast.success(res?.data?.message);
        } catch (err) {
            playSound(WarningSound);
            toast.error(getErrorMessage(err));
        } finally {
            barcodeInput.current?.focus();
        }
    }
    function cartEmpty() {
        if (total <= 0) {
            return;
        }
        Swal.fire({
            title: translate("Are you sure you want to delete Cart?"),
            showDenyButton: true,
            confirmButtonText: translate("Yes"),
            denyButtonText: translate("No"),
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/cart/empty")
                    .then((res) => {
                        setCartUpdated(previous => !previous);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        playSound(WarningSound);
                        toast.error(getErrorMessage(err));
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    function orderCreate() {
        if (total <= 0) {
            return;
        }
        if (!customerId) {
            toast.error(translate("Please select customer"));
            return;
        }
        const balanceLine =
            parseFloat(change) > 0
                ? `${translate("Change to return")}: ${change}`
                : `${translate("Due")}: ${due}`;
        Swal.fire({
            title: `${translate("Are you sure you want to complete this order?")} <br>${balanceLine}`,
            showDenyButton: true,
            confirmButtonText: translate("Yes"),
            denyButtonText: translate("No"),
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/order/create", {
                        customer_id: customerId,
                        order_discount: parseFloat(orderDiscount) || 0,
                        paid: parseFloat(paid) || 0,
                    })
                    .then((res) => {
                        setCartUpdated(previous => !previous);
                        setProductUpdated(previous => !previous);
                        toast.success(res?.data?.message);
                        // window.location.href = `orders/invoice/${res?.data?.order?.id}`;
                        window.location.href = `orders/pos-invoice/${res?.data?.order?.id}`;
                    })
                    .catch((err) => {
                        playSound(WarningSound);
                        toast.error(getErrorMessage(err), { duration: 6000 });
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    return (
        <div className="qpos-pos-grid">
            <nav className="qpos-workspace-tabs" role="tablist" aria-label={translate('POS')} onKeyDown={event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 'catalogue' : event.key === 'End' ? 'checkout' : activePanel === 'catalogue' ? 'checkout' : 'catalogue';
                setActivePanel(next);
                document.getElementById('pos-tab-' + next)?.focus();
            }}>
                <button id="pos-tab-catalogue" type="button" role="tab" aria-selected={activePanel === 'catalogue'} aria-controls="pos-panel-catalogue" tabIndex={activePanel === 'catalogue' ? 0 : -1} onClick={() => setActivePanel('catalogue')}><Package size={18} aria-hidden="true" />{translate('Products')}</button>
                <button id="pos-tab-checkout" type="button" role="tab" aria-selected={activePanel === 'checkout'} aria-controls="pos-panel-checkout" tabIndex={activePanel === 'checkout' ? 0 : -1} onClick={() => setActivePanel('checkout')}><ShoppingCart size={18} aria-hidden="true" />{translate('Cart')} <span className="qpos-badge qpos-badge-info">{carts.length}</span></button>
            </nav>
            <section id="pos-panel-catalogue" role="tabpanel" className={'qpos-card qpos-catalogue-panel ' + (activePanel === 'catalogue' ? 'is-active' : '')} aria-labelledby="pos-tab-catalogue">
                <header className="qpos-workspace-heading"><div><span className="qpos-eyebrow">{translate("POS")}</span><h2 id="qpos-catalogue-title">{translate("Product catalogue")}</h2></div><Package size={24} aria-hidden="true" /></header>
                <div className="qpos-search-grid">
                    <Field label={translate("Enter Product Barcode")}><span className="qpos-input-icon"><Barcode size={18} aria-hidden="true" /><input ref={barcodeInput} className="qpos-control" type="text" value={searchBarcode} onChange={e => setSearchBarcode(e.target.value)} onKeyDown={e => { if (e.key === 'Enter' && searchBarcode.trim()) { e.preventDefault(); const barcode = searchBarcode.trim(); setSearchBarcode(""); getProducts('',1,barcode); } }} /></span></Field>
                    <Field label={translate("Search products")}><span className="qpos-input-icon"><Search size={18} aria-hidden="true" /><input className="qpos-control" type="search" placeholder={translate("Enter Product Name")} value={searchQuery} onChange={e => setSearchQuery(e.target.value)} /></span></Field>
                </div>
                <div className="qpos-products-grid" aria-busy={loading}>
                    {products.map(product => <button type="button" className="qpos-product" key={product.id} onClick={() => addProductToCart(product.id)}>
                        <img src={window.qposStorageUrl + '/' + product.image} alt="" loading="lazy" width="160" height="128" onError={e => { e.target.onerror=null; e.target.src=window.qposFallbackImage; }} />
                        <span className="qpos-product-name">{product.name}</span>
                        <span className="qpos-product-stock">{translate("Stock")}: {product.quantity}</span>
                        <strong>{product.discounted_price}</strong>
                    </button>)}
                </div>
                {loading ? <p className="qpos-workspace-status" role="status">{translate("Loading more...")}</p> : !products.length && <EmptyState title={translate("No products found")} description={translate("Search products")} />}
                {currentPage < totalPages && <div className="qpos-workspace-footer"><button type="button" className="qpos-button qpos-button-md qpos-button-secondary" disabled={loading} onClick={() => getProducts(searchQuery, currentPage+1)}>{translate("Load more products")}</button></div>}
            </section>
            <section id="pos-panel-checkout" role="tabpanel" className={'qpos-card qpos-checkout-panel ' + (activePanel === 'checkout' ? 'is-active' : '')} aria-labelledby="pos-tab-checkout">
                <header className="qpos-workspace-heading"><div><span className="qpos-eyebrow">{translate("Sale")}</span><h2 id="qpos-summary-title">{translate("Order summary")}</h2></div><ShoppingCart size={24} aria-hidden="true" /></header>
                <div className="qpos-workspace-body"><label className="qpos-field-label" htmlFor="pos-customer">{translate("Customer")}</label><CustomerSelect setCustomerId={setCustomerId} /></div>
                <Cart carts={carts} setCartUpdated={setCartUpdated} cartUpdated={cartUpdated} />
                <div className="qpos-totals">
                    <div><span>{translate("Sub Total:")}</span><strong>{total}</strong></div>
                    <label><span>{translate("Discount:")}</span><input className="qpos-control" type="number" min="0" disabled={total<=0} value={orderDiscount} onChange={e => { const value=e.target.value; if(parseFloat(value)>total || parseFloat(value)<0) return; setOrderDiscount(value); }} /></label>
                    <label className="qpos-check-row"><span>{translate("Apply Fractional Discount:")}</span><input type="checkbox" disabled={total<=0} onChange={e => setOrderDiscount(e.target.checked ? (total % 1).toFixed(2) : 0)} /></label>
                    <div className="qpos-total-highlight"><span>{translate("Total:")}</span><strong>{updateTotal}</strong></div>
                    <label><span>{translate("Paid:")}</span><input className="qpos-control" type="number" min="0" disabled={total<=0} value={paid} onChange={e => { if(parseFloat(e.target.value)<0) return; setPaid(e.target.value); }} /></label>
                    <div><span>{translate("Due")}</span><strong>{due}</strong></div>
                    {parseFloat(change)>0 && <div className="qpos-success"><span>{translate("Change:")}</span><strong>{change}</strong></div>}
                </div>
                <div className="qpos-checkout-actions"><button type="button" className="qpos-button qpos-button-md qpos-button-danger" disabled={total<=0} onClick={cartEmpty}><Trash2 size={18} aria-hidden="true" />{translate("Clear Cart")}</button><button type="button" className="qpos-button qpos-button-lg qpos-button-primary" disabled={total<=0} onClick={orderCreate}><Check size={20} aria-hidden="true" />{translate("Checkout")}</button></div>
            </section>
            <WorkspaceToaster />
        </div>
    );
}
