import React, { useCallback, useEffect, useState } from "react";
import Suppliers from "./Suppliers";
import axios from "axios";
import Swal from "sweetalert2";
import { toast, Toaster } from "sonner";
import DatePicker, { registerLocale } from "react-datepicker";
import { enUS } from "date-fns/locale/en-US";
import { fr } from "date-fns/locale/fr";
import "react-datepicker/dist/react-datepicker.css";
import translate from "../../utils/translate";
import useDocumentTheme from "../../utils/useDocumentTheme";
import { Search } from "lucide-react";

registerLocale("fr", fr);
registerLocale("en", enUS);

export default function Purchase() {
    const theme = useDocumentTheme();
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
                    window.location.href = "/admin/purchase";
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
        // Define the asynchronous function
        async function getProducts() {
            if (!searchTerm.trim()) {
                setSearchResults([]);
                return;
            }

            try {
                const res = await axios.get("/admin/products", {
                    params: { search: searchTerm },
                });

                const productsData = res.data;
                setSearchResults(productsData?.data || []);
            } catch (error) {
                console.error("Error fetching products:", error);
            }
        }
        // Call the async function inside useEffect
        getProducts();
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
        <>
            <div className="container-fluid qpos-purchase">
                <div className="card">
                    <div className="card-body">
                        <div className="row">
                            <div className="mb-3 col-md-6">
                                <label htmlFor="date" className="form-label">
                                    {translate("Purchase Date")}
                                    <span className="text-danger">*</span>
                                </label>
                                <div>
                                    <DatePicker
                                        name="date"
                                        className="form-control"
                                        placeholderText={translate("Enter purchase date")}
                                        selected={date ? new Date(`${date}T00:00:00`) : null}
                                        dateFormat="yyyy-MM-dd"
                                        locale={window.qposLocale === "fr" ? "fr" : "en"}
                                        onChange={(date) => {
                                            const formattedDate = date
                                                ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`
                                                : null;
                                            setDate(formattedDate);
                                        }}
                                    />
                                </div>
                            </div>
                            <div className="mb-3 col-md-6">
                                <label
                                    htmlFor="supplier"
                                    className="form-label"
                                >
                                    {translate("Supplier")}
                                    <span className="text-danger">*</span>
                                </label>
                                <Suppliers
                                    setSupplierId={setSupplierId}
                                    oldSupplier={selectedSupplier}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="row mb-2">
                            <div className="input-group col-6">
                                <div className="input-group-prepend">
                                    <span className="input-group-text">
                                        <Search size={18} aria-hidden="true" />
                                    </span>
                                </div>
                                <input
                                    type="search"
                                    className="form-control form-control-lg"
                                    value={searchTerm}
                                    onChange={(e) =>
                                        setSearchTerm(e.target.value)
                                    }
                                    placeholder={translate("Enter product barcode/name")}
                                />
                                <button
                                    className="btn bg-gradient-primary ml-2"
                                    onClick={handleSearchAdd}
                                >
                                    {translate("Add Product")}
                                </button>
                            </div>
                        </div>
                        {/* Display search results below the input */}
                        {searchResults.length > 0 && (
                            <div className="row mb-2">
                                <div
                                    className="col-6"
                                    style={{
                                        maxHeight: "200px",
                                        overflowY: "auto",
                                    }}
                                >
                                    <ul className="list-group">
                                        {searchResults.map((product) => (
                                            <li
                                                key={product.id}
                                                className="list-group-item"
                                                onClick={() =>
                                                    handleProductSelect(product)
                                                }
                                                style={{ cursor: "pointer" }}
                                            >
                                                {product.name} - $
                                                {product.price}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        )}
                        <div className="row">
                            <div className="col-12">
                                <table className="table table-sm table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{translate("Product Name")}</th>
                                            <th>{translate("Purchase Price")}</th>
                                            <th>{translate("Current Stock")}</th>
                                            <th>{translate("Qty")}</th>
                                            <th>{translate("Sub Total")}</th>
                                            <th>{translate("Action")}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {products.map((product, index) => (
                                        <tr key={product.id}>
                                                <td>{index + 1}</td>
                                                <td>{product.name}</td>
                                                <td className="d-flex align-items-center justify-content-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        className="form-control w-50"
                                                        value={
                                                            product.purchase_price
                                                        }
                                                        onChange={(e) =>
                                                            handlePriceChange(
                                                                product.id,
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td>{product.stock}</td>
                                                <td className="d-flex align-items-center justify-content-center">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        className="form-control w-50"
                                                        value={product.qty}
                                                        onChange={(e) =>
                                                            handleQtyChange(
                                                                product.id,
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td>
                                                    {product.subTotal.toFixed(
                                                        2
                                                    )}
                                                </td>
                                                <td>
                                                    <button
                                                        className="btn btn-danger btn-sm"
                                                        onClick={() =>
                                                            handleDelete(
                                                                product.id
                                                            )
                                                        }
                                                    >
                                                        {translate("Delete")}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-6"></div>
                            <div className="col-6">
                                <div className="table-responsive">
                                    <table className="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th>{translate("Subtotal:")}</th>
                                                <td className="text-right">
                                                    {totals.subTotal.toFixed(2)}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{translate("Tax:")}</th>
                                                <td className="text-right">
                                                    {totals.tax.toFixed(2)}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{translate("Discount:")}</th>
                                                <td className="text-right">
                                                    {totals.discount.toFixed(2)}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{translate("Shipping:")}</th>
                                                <td className="text-right">
                                                    {totals.shipping.toFixed(2)}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{translate("Grand Total:")}</th>
                                                <td className="text-right">
                                                    {totals.grandTotal.toFixed(
                                                        2
                                                    )}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="row">
                            <div className="mb-3 col-md-4">
                                <label htmlFor="tax" className="form-label">
                                    {translate("Tax")}
                                </label>
                                <input
                                    type="number"
                                    className="form-control"
                                    value={tax}
                                    min="0"
                                    onChange={(e) =>
                                        setTax(parseFloat(e.target.value) || 0)
                                    }
                                    placeholder={translate("Enter tax")}
                                    name="tax"
                                    required
                                />
                            </div>
                            <div className="mb-3 col-md-4">
                                <label
                                    htmlFor="discount"
                                    className="form-label"
                                >
                                    {translate("Discount")}
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    className="form-control"
                                    value={discount}
                                    onChange={(e) =>
                                        setDiscount(
                                            parseFloat(e.target.value) || 0
                                        )
                                    }
                                    placeholder={translate("Enter discount")}
                                    name="discount"
                                    required
                                />
                            </div>
                            <div className="mb-3 col-md-4">
                                <label
                                    htmlFor="shipping"
                                    className="form-label"
                                >
                                    {translate("Shipping Charge")}
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    className="form-control"
                                    value={shipping}
                                    onChange={(e) =>
                                        setShipping(
                                            parseFloat(e.target.value) || 0
                                        )
                                    }
                                    placeholder={translate("Enter shipping")}
                                    name="shipping"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <button
                    type="submit"
                    className="btn btn-md bg-gradient-primary"
                    onClick={handleSubmit}
                >
                    {translate(purchaseId ? "Update" : "Create")}
                </button>
            </div>

            <Toaster position="top-right" richColors closeButton theme={theme} />
        </>
    );
}
