// import './bootstrap';
// The shared shell loads app.css once in its head.
import React from 'react'
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import translate from './utils/translate';

axios.defaults.baseURL = window.qposBaseUrl;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Pin this page's shop so another tab cannot silently move its operation.
axios.interceptors.request.use((config) => {
    if (window.qposOperationShopId && String(config.url ?? '').startsWith('/admin/')) {
        config.params = { ...config.params, operation_point_of_sale_id: window.qposOperationShopId };
    }
    return config;
});
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;

function showLoadingError(element, error) {
    console.error('Unable to load workspace:', error);
    element.replaceChildren();
    const panel = document.createElement('div');
    panel.className = 'qpos-card qpos-state qpos-state-error';
    panel.setAttribute('role', 'alert');
    const title = document.createElement('h3');
    title.textContent = translate('Unable to load workspace');
    const button = document.createElement('button');
    button.className = 'qpos-button qpos-button-md qpos-button-secondary';
    button.textContent = translate('Reload page');
    button.addEventListener('click', () => window.location.reload());
    panel.append(title, button);
    element.append(panel);
}
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
        .catch((error) => showLoadingError(cartElement, error));
}

// Check for the 'purchase' element and render the 'Purchase' component using createRoot
const purchaseElement = document.getElementById("purchase");
if (purchaseElement) {
    const purchaseRoot = createRoot(purchaseElement);
    import("./components/Purchase/Purchase")
        .then(({ default: Purchase }) => purchaseRoot.render(<Purchase />))
        .catch((error) => showLoadingError(purchaseElement, error));
}
