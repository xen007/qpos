import React, { useCallback, useEffect, useState } from "react";
import Suppliers from "./Suppliers";
import axios from "axios";
import Swal from "sweetalert2";
import { toast } from "sonner";
import translate from "../../utils/translate";

import { Search, Package, Plus, Trash2, Check } from "lucide-react";
import { Field, EmptyState, WorkspaceToaster } from "../WorkspaceUI";


export default function Purchase() {

    const [searchTerm, setSearchTerm] = useState("");
    const [barcode, setBarcode] = useState("");
    const [selectedSupplier, setSelectedSupplier] = useState(null);
    const [purchaseId, setPurchaseId] = useState(null);
    const [date, setDate] = useState(null);
    const [supplierId, setSupplierId] = useState(null);
    const [tax, setTax] = useState(0);
    const [discount, setDiscount] = useState(0);
    const [shipping, setShipping] = useState(0);
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
            setDate(purchaseData?.date ? purchaseData.date.split(" ")[0] : "");
            setSelectedSupplier({
                value: purchaseData?.supplier_id,
                label: purchaseData?.supplier?.name,
            });
            setSupplierId(purchaseData?.supplier_id ?? null);
            setTax(purchaseData?.tax);
            setDiscount(purchaseData?.discount_value);
            setShipping(purchaseData?.shipping);
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

        // Optional: Uncomment if you want to show loading state
        // setLoading(true);

        try {
            const res = await axios.get("/admin/products", {
                params: { search: searchTerm },
            });

            const productsData = res.data;

            // Ensure productsData and productsData.data exist
            if (productsData?.data && productsData.data.length) {
                productsData.data.forEach((product) => {
                    const existingProductIndex = products.findIndex(
                        (p) => p.id === product.id
                    );
                    if (existingProductIndex !== -1) {
                        // Product exists, increment qty
                        setProducts((prevProducts) => {
                            const updatedProducts = [...prevProducts];
                            updatedProducts[existingProductIndex].qty += 1; // Increment qty
                            updatedProducts[existingProductIndex].subTotal =
                                updatedProducts[existingProductIndex]
                                    .purchase_price *
                                updatedProducts[existingProductIndex].qty; // Update subTotal
                            return updatedProducts;
                        });
                    } else {
                        // New product, add to the list
                        const newProduct = {
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            purchase_price: product.purchase_price,
                            stock: product.quantity,
                            qty: 1,
                            subTotal: product.purchase_price,
                        };
                        setProducts((prevProducts) => [
                            ...prevProducts,
                            newProduct,
                        ]);
                    }
                });
            }
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            // Optional: Uncomment if you want to hide loading state
            // setLoading(false);

            // Clear searchTerm if needed
            setSearchTerm("");
        }
    }, [searchTerm]); // Don't forget to add searchTerm as a dependency

    // Handle deletion of a product
    const handleDelete = (id) => {
        setProducts(products.filter((product) => product.id !== id));
    };

    // Update quantity and recalculate subtotal
    const handleQtyChange = (id, value) => {
        const updatedProducts = products.map((product) => {
            if (product.id === id) {
                const newQty = parseInt(value) || 0;
                return {
                    ...product,
                    qty: newQty,
                    subTotal: parseFloat(
                        (product.purchase_price * newQty).toFixed(2)
                    ),
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };

    // Update purchase price and recalculate subtotal
    const handlePriceChange = (id, value) => {
        const updatedProducts = products.map((product) => {
            if (product.id === id) {
                const newPrice = parseFloat(value) || 0;
                return {
                    ...product,
                    purchase_price: newPrice,
                    subTotal: parseFloat((product.qty * newPrice).toFixed(2)),
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };
    // Add a new product by searching
    const handleSearchAdd = () => {
        getProducts();
    };

    // Calculate totals with two decimal places
    const calculateTotals = () => {
        const subTotal = products.reduce(
            (sum, product) => sum + product.subTotal,
            0
        );
        const formattedSubTotal = parseFloat(subTotal.toFixed(2));
        const formattedTax = parseFloat((tax || 0).toFixed(2));
        const formattedDiscount = parseFloat((discount || 0).toFixed(2));
        const formattedShipping = parseFloat((shipping || 0).toFixed(2));
        const grandTotal = parseFloat(
            (
                formattedSubTotal +
                formattedTax -
                formattedDiscount +
                formattedShipping
            ).toFixed(2)
        );

        return {
            subTotal: formattedSubTotal,
            tax: formattedTax,
            discount: formattedDiscount,
            shipping: formattedShipping,
            grandTotal,
        };
    };

    const totals = calculateTotals();
    const handleSubmit = async () => {
        if (totals.grandTotal <= 0) {
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
                        purchase_id: purchaseId,
                        date,
                        products,
                        supplierId,
                        totals,
                    });
                    setProducts([]);
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
        const existingProductIndex = products.findIndex(
            (p) => p.id === product.id
        );

        if (existingProductIndex !== -1) {
            // If product exists, increment quantity
            setProducts((prevProducts) => {
                const updatedProducts = [...prevProducts];
                updatedProducts[existingProductIndex].qty += 1;
                updatedProducts[existingProductIndex].subTotal =
                    updatedProducts[existingProductIndex].purchase_price *
                    updatedProducts[existingProductIndex].qty;
                return updatedProducts;
            });
        } else {
            // Add new product to the list
            const newProduct = {
                id: product.id,
                name: product.name,
                price: product.price,
                purchase_price: product.purchase_price,
                stock: product.quantity,
                qty: 1,
                subTotal: product.purchase_price,
            };
            setProducts((prevProducts) => [...prevProducts, newProduct]);
        }

        // Clear search term and results
        setSearchTerm("");
        setSearchResults([]);
    };
    return (
        <div className="qpos-purchase-workspace">
            <section className="qpos-card">
                <header className="qpos-workspace-heading"><h2>{translate(purchaseId ? "Edit Purchase" : "Purchase Create")}</h2><Package size={24} aria-hidden="true" /></header>
                <div className="qpos-workspace-body qpos-search-grid">
                    <Field label={translate("Purchase Date")}><input id="date" type="date" className="qpos-control" required value={date || ''} onChange={e => setDate(e.target.value || null)} /></Field>
                    <div><label className="qpos-field-label" htmlFor="purchase-supplier">{translate("Supplier")}</label><Suppliers setSupplierId={setSupplierId} oldSupplier={selectedSupplier} /></div>
                </div>
            </section>
            <section className="qpos-card">
                <header className="qpos-workspace-heading"><h2>{translate("Purchase items")}</h2><span className="qpos-badge qpos-badge-info">{products.length}</span></header>
                <div className="qpos-workspace-body">
                    <form className="qpos-purchase-search" onSubmit={e => { e.preventDefault(); handleSearchAdd(); }}><Field label={translate("Search products")}><span className="qpos-input-icon"><Search size={18} aria-hidden="true" /><input type="search" className="qpos-control" placeholder={translate("Enter product barcode/name")} value={searchTerm} onChange={e => setSearchTerm(e.target.value)} /></span></Field><button className="qpos-button qpos-button-md qpos-button-primary" type="submit"><Plus size={18} aria-hidden="true" />{translate("Add Product")}</button></form>
                    {!!searchResults.length && <ul className="qpos-search-results" aria-label={translate("Search products")}>{searchResults.map(product => <li key={product.id}><button type="button" onClick={() => handleProductSelect(product)}><span>{product.name}</span><strong>{product.price}</strong></button></li>)}</ul>}
                </div>
                {!products.length ? <EmptyState title={translate("Add products to your purchase")} description={translate("Enter product barcode/name")} /> : <div className="qpos-table-scroll" tabIndex="0" role="region" aria-label={translate("Purchase items")}><table className="qpos-table qpos-purchase-table"><thead><tr><th>#</th><th>{translate("Product Name")}</th><th>{translate("Purchase Price")}</th><th>{translate("Current Stock")}</th><th>{translate("Qty")}</th><th>{translate("Sub Total")}</th><th>{translate("Action")}</th></tr></thead><tbody>{products.map((product,index) => <tr key={product.id}><td>{index+1}</td><td data-label={translate("Product Name")}><strong>{product.name}</strong></td><td data-label={translate("Purchase Price")}><input type="number" min="0" className="qpos-control" aria-label={translate("Purchase Price") + ': ' + product.name} value={product.purchase_price} onChange={e => handlePriceChange(product.id,e.target.value)} /></td><td data-label={translate("Current Stock")}>{product.stock}</td><td data-label={translate("Qty")}><input type="number" min="1" className="qpos-control" aria-label={translate("Qty") + ': ' + product.name} value={product.qty} onChange={e => handleQtyChange(product.id,e.target.value)} /></td><td data-label={translate("Sub Total")}>{product.subTotal.toFixed(2)}</td><td><button type="button" className="qpos-icon-button qpos-icon-button-danger" aria-label={translate("Delete") + ': ' + product.name} onClick={() => handleDelete(product.id)}><Trash2 size={18} aria-hidden="true" /></button></td></tr>)}</tbody></table></div>}
            </section>
            <div className="qpos-search-grid">
                <section className="qpos-card"><header className="qpos-workspace-heading"><h2>{translate("Information")}</h2></header><div className="qpos-workspace-body qpos-field-stack"><Field label={translate("Tax")}><input type="number" className="qpos-control" min="0" value={tax} onChange={e => setTax(parseFloat(e.target.value)||0)} /></Field><Field label={translate("Discount")}><input type="number" className="qpos-control" min="0" value={discount} onChange={e => setDiscount(parseFloat(e.target.value)||0)} /></Field><Field label={translate("Shipping")}><input type="number" className="qpos-control" min="0" value={shipping} onChange={e => setShipping(parseFloat(e.target.value)||0)} /></Field></div></section>
                <section className="qpos-card"><header className="qpos-workspace-heading"><h2>{translate("Order summary")}</h2></header><div className="qpos-totals">{[['Subtotal:',totals.subTotal],['Tax:',totals.tax],['Discount:',totals.discount],['Shipping:',totals.shipping]].map(([label,value]) => <div key={label}><span>{translate(label)}</span><strong>{value.toFixed(2)}</strong></div>)}<div className="qpos-total-highlight"><span>{translate("Grand Total:")}</span><strong>{totals.grandTotal.toFixed(2)}</strong></div></div><div className="qpos-workspace-footer"><button type="button" className="qpos-button qpos-button-lg qpos-button-primary" disabled={totals.grandTotal<=0} onClick={handleSubmit}><Check size={20} aria-hidden="true" />{translate(purchaseId ? "Update" : "Create")}</button></div></section>
            </div>
            <WorkspaceToaster />
        </div>
    );
}
