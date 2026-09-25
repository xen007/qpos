// import './bootstrap';
import "../css/app.css";
import React from 'react'
import { createRoot } from 'react-dom/client';
// export default function app() {
//   return (
//     <Pos />
//   )
// }

// Check for the 'cart' element and render the 'cart' component using createRoot
const cartElement = document.getElementById("cart");
if (cartElement) {
    const cartRoot = createRoot(cartElement);
    import("./components/Pos")
        .then(({ default: Pos }) => cartRoot.render(<Pos />))
        .catch((error) => console.error("Unable to load the POS screen:", error));
}

// Check for the 'purchase' element and render the 'Purchase' component using createRoot
const purchaseElement = document.getElementById("purchase");
if (purchaseElement) {
    const purchaseRoot = createRoot(purchaseElement);
    import("./components/Purchase/Purchase")
        .then(({ default: Purchase }) => purchaseRoot.render(<Purchase />))
        .catch((error) => console.error("Unable to load the purchase screen:", error));
}
