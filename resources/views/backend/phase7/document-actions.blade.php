<style>
.document-actions{display:flex;flex-wrap:wrap;gap:8px;margin:20px 0}.document-action{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 18px;border:1px solid #164e63;border-radius:8px;background:#164e63;color:#fff;font:600 14px Arial;text-decoration:none;cursor:pointer}.document-action.secondary{background:#fff;color:#164e63}.document-action:focus-visible{outline:3px solid #0891b2;outline-offset:3px}body{padding:12px} @media screen and (max-width:390px){body{max-width:100%;width:100%}table{font-size:11px}.totals{width:100%}}@media print{.document-actions{display:none}body{padding:0}}
</style>
<nav class="document-actions" aria-label="{{ __('Document actions') }}">
<button type="button" class="document-action" onclick="window.print()">{{ __('Print') }}</button>
@if(auth()->user()?->hasAnyPermission(['sale_create','pending_sale_prepare']))
<a class="document-action secondary" href="{{ route('backend.admin.cart.index') }}">{{ __('POS') }}</a>
@endif
@if(auth()->user()?->can('sale_view'))
<a class="document-action secondary" href="{{ route('backend.admin.orders.index') }}">{{ __('Sales') }}</a>
@endif
</nav>
@if(!empty($launchPrint))
<script>
(() => {
    const url = new URL(window.location.href);
    const nonce = url.searchParams.get('print_nonce');
    const key = 'qpos-print:' + url.pathname + ':' + (nonce || '');
    let already = false;
    try { already = sessionStorage.getItem(key) === '1'; sessionStorage.setItem(key, '1'); } catch (_) {}
    url.searchParams.delete('print'); url.searchParams.delete('auto_print'); url.searchParams.delete('print_nonce');
    window.history.replaceState(null, '', url.toString());
    if (already) return;
    const launch = () => window.setTimeout(() => window.print(), 100);
    if (document.readyState === 'complete') launch();
    else window.addEventListener('load', launch, {once: true});
})();
</script>
@endif
