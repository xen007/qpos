<?php

namespace App\Support;

/**
 * Arbre de navigation du back-office.
 *
 * Source unique : la liste des entrees, leur ordre, leurs icones et surtout
 * leurs conditions d'acces ne sont definis qu'ici. Les vues ne font que
 * l'afficher, chacune avec sa propre mise en forme :
 *   - backend.layouts.sidebar          (skin AdminLTE, pages en migration)
 *   - backend.layouts.tailwind.sidebar (skin Tailwind, pages migrees)
 *
 * Regles de construction :
 *   - une entree n'est creee que si l'utilisateur possede la permission ;
 *   - un groupe vide n'est jamais rendu (pas de flechon sans enfant) ;
 *   - l'etat actif d'un groupe est deduit de ses enfants, jamais declare.
 */
class BackendMenu
{
    public const TYPE_ITEM = 'item';

    public const TYPE_GROUP = 'group';

    public const TYPE_HEADER = 'header';

    /**
     * Entrees de navigation visibles par l'utilisateur connecte.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $items = [];

        if ($user->can('dashboard_view')) {
            $items[] = self::item('Dashboard', 'fas fa-tachometer-alt', 'backend.admin.dashboard');
            $items[] = self::item('Statistics', 'fas fa-chart-bar', 'backend.admin.reporting.statistics');
        }

        if ($user->can('sale_create')) {
            $items[] = self::item('POS', 'fas fa-cart-plus', 'backend.admin.cart.index');
        }
        if ($user->can('cash_session_manage')) {
            $items[] = self::item('Cash sessions', 'fas fa-cash-register', 'backend.admin.cash.index', ['backend.admin.cash.*']);
            $items[] = self::item('reporting.workday', 'fas fa-cash-register', 'backend.admin.reporting.workday');
        }
        if ($user->can('expense_view')) {
            $items[] = self::item('Expenses', 'fas fa-wallet', 'backend.admin.expenses.index', ['backend.admin.expenses.*']);
        }
        if ($user->can('price_labels')) {
            $items[] = self::item('Price labels', 'fas fa-barcode', 'backend.admin.labels.index', ['backend.admin.labels.*']);
        }

        // --- Personnes ------------------------------------------------------
        $items[] = self::group('People', 'fas fa-user-circle', [
            $user->hasAnyPermission([
                'customer_create', 'customer_view', 'customer_update', 'customer_delete',
            ])
                ? self::item('Customer', 'fas fa-circle', 'backend.admin.customers.index', [
                    'backend.admin.customers.create',
                    'backend.admin.customers.edit',
                ])
                : null,
            $user->hasAnyPermission([
                'supplier_create', 'supplier_view', 'supplier_update', 'supplier_delete',
            ])
                ? self::item('Supplier', 'fas fa-circle', 'backend.admin.suppliers.index', [
                    'backend.admin.suppliers.create',
                    'backend.admin.suppliers.edit',
                ])
                : null,
        ]);

        if ($user->can('point_of_sale_view') && PointOfSaleContext::ready()) {
            $items[] = self::item('Stores', 'fas fa-store', 'backend.admin.shops.index', ['backend.admin.shops.*']);
        }

        if ($user->can('pricing_view') && PricingSchema::ready()) {
            $items[] = self::item('Prices and promotions', 'fas fa-tags', 'backend.admin.pricing.index', ['backend.admin.pricing.*']);
        }

        // --- Produits -------------------------------------------------------
        $items[] = self::group('Product', 'fas fa-box', [
            $user->hasAnyPermission([
                'product_view', 'product_update', 'product_delete',
            ])
                ? self::item('Product List', 'fas fa-circle', 'backend.admin.products.index', [
                    'backend.admin.products.edit',
                ])
                : null,
            $user->can('product_create')
                ? self::item('Product Create', 'fas fa-circle', 'backend.admin.products.create')
                : null,
            $user->can('product_import')
                ? self::item('Product Import', 'fas fa-circle', 'backend.admin.products.import')
                : null,
            $user->hasAnyPermission([
                'brand_create', 'brand_view', 'brand_update', 'brand_delete',
            ])
                ? self::item('Brand', 'fas fa-circle', 'backend.admin.brands.index', [
                    'backend.admin.brands.create',
                    'backend.admin.brands.edit',
                ])
                : null,
            $user->hasAnyPermission([
                'category_create', 'category_view', 'category_update', 'category_delete',
            ])
                ? self::item('Category', 'fas fa-circle', 'backend.admin.categories.index', [
                    'backend.admin.categories.create',
                    'backend.admin.categories.edit',
                ])
                : null,
            $user->hasAnyPermission([
                'unit_create', 'unit_view', 'unit_update', 'unit_delete',
            ])
                ? self::item('Unit', 'fas fa-circle', 'backend.admin.units.index', [
                    'backend.admin.units.create',
                    'backend.admin.units.edit',
                ])
                : null,
        ]);

        // --- Ventes ---------------------------------------------------------
        $items[] = self::group('Sale', 'fas fa-tags', [
            $user->can('sale_view')
                ? self::item('Sale List', 'fas fa-circle', 'backend.admin.orders.index')
                : null,
        ]);

        // --- Achats ---------------------------------------------------------
        $items[] = self::group('Purchase', 'fas fa-shopping-bag', [
            $user->can('purchase_view')
                ? self::item('Purchase List', 'fas fa-circle', 'backend.admin.purchase.index')
                : null,
            $user->can('purchase_create')
                ? self::item('Purchase Create', 'fas fa-circle', 'backend.admin.purchase.create')
                : null,
        ]);

        $items[] = self::group('Stock operations', 'fas fa-box', [
            (bool) config('system.multi_shop_enabled', true) && $user->can('stock_view') ? self::item('Stock transfers', 'fas fa-circle', 'backend.admin.stock.transfers', ['backend.admin.stock.transfers.*']) : null,
            $user->can('stock_inventory') ? self::item('Physical inventories', 'fas fa-circle', 'backend.admin.stock.inventories', ['backend.admin.stock.inventories.*']) : null,
            $user->can('stock_opening_approve') ? self::item('Openings awaiting approval', 'fas fa-circle', 'backend.admin.stock.openings', ['backend.admin.stock.openings.*']) : null,
            $user->can('stock_auto_lots_manage') ? self::item('Lots automatiques', 'fas fa-circle', 'backend.admin.stock.automatic-lots', ['backend.admin.stock.automatic-lots*']) : null,
            $user->can('stock_view') ? self::item('Expiry alerts', 'fas fa-circle', 'backend.admin.stock.expiry-alerts') : null,
        ]);

        // --- Rapports Phase 5 -----------------------------------------------
        $reports = [];
        foreach (\App\Services\ReportingService::TYPES as $type => $permission) {
            if ($user->can($permission) && ($type !== 'history' || $user->hasRole('Admin'))) {
                $reports[] = self::item('reporting.'.$type, 'fas fa-circle', 'backend.admin.reporting.index', [], '', ['type' => $type]);
            }
        }
        if ($user->hasAllPermissions(['daily_summary_view', 'reports_summary', 'reports_sales', 'reports_inventory'])) {
            $reports[] = self::item('reporting.daily_summary', 'fas fa-circle', 'backend.admin.reporting.summaries');
        }
        $items[] = self::group('Reports', 'fas fa-chart-bar', $reports);
        // --- Reglages -------------------------------------------------------
        $settings = self::group('Website Settings', 'fas fa-cog', [
            $user->hasRole('Admin') && $user->hasAllPermissions(['daily_summary_close', 'daily_summary_view', 'reports_summary', 'reports_sales', 'reports_inventory', 'user_view', 'point_of_sale_manage_all'])
                ? self::item('reporting.summary_settings', 'fas fa-envelope', 'backend.admin.reporting.summary-settings') : null,
            $user->hasAnyPermission([
                'website_settings', 'contact_settings', 'socials_settings', 'style_settings',
                'custom_settings', 'notification_settings', 'website_status_settings',
                'invoice_settings',
            ])
                ? self::item(
                    'General Settings',
                    'fas fa-circle',
                    'backend.admin.settings.website.general',
                    [],
                    '?active-tab=website-info'
                )
                : null,
            $user->can('website_settings') ? self::item('System Settings', 'fas fa-sliders-h', 'backend.admin.settings.system') : null,
            $user->hasAnyPermission([
                'currency_create', 'currency_view', 'currency_update', 'currency_delete',
            ])
                ? self::item('Currency', 'fas fa-coins', 'backend.admin.currencies.index', [
                    'backend.admin.currencies.create',
                    'backend.admin.currencies.edit',
                ])
                : null,
            $user->hasAnyPermission([
                'role_create', 'role_view', 'role_update', 'role_delete', 'permission_view',
            ])
                ? self::group('Roles & Permissions', 'fas fa-chevron-circle-right', [
                    $user->can('role_view')
                        ? self::item('Roles', 'far fa-circle', 'backend.admin.roles')
                        : null,
                    $user->can('permission_view')
                        ? self::item('Permissions', 'far fa-circle', 'backend.admin.permissions')
                        : null,
                ])
                : null,
            $user->hasAnyPermission([
                'user_create', 'user_view', 'user_update', 'user_delete', 'user_suspend',
            ])
                ? self::item('User Management', 'fas fa-circle', 'backend.admin.users')
                : null,
        ]);

        if ($settings !== null) {
            $items[] = self::header('Settings');
            $items[] = $settings;
        }

        return self::compact($items);
    }

    /**
     * Entree de menu pointant vers une route nommee.
     *
     * La route nommee sert de motif d'etat actif par defaut ; les routes
     * filles (create, edit...) se declarent dans $extraPatterns.
     *
     * @param  array<int, string>  $extraPatterns
     */
    protected static function item(
        string $label,
        string $icon,
        string $route,
        array $extraPatterns = [],
        string $urlSuffix = '',
        array $parameters = []
    ): array {
        return [
            'type' => self::TYPE_ITEM,
            'label' => __($label),
            'icon' => $icon,
            'url' => route($route, $parameters).$urlSuffix,
            'active' => request()->routeIs(array_merge([$route], $extraPatterns)),
            'children' => [],
        ];
    }

    /**
     * Groupe repliable. Retourne null si aucun enfant n'est visible : un
     * groupe sans enfant ne doit pas apparaitre dans la navigation.
     *
     * @param  array<int, array<string, mixed>|null>  $children
     */
    protected static function group(string $label, string $icon, array $children): ?array
    {
        $children = self::compact($children);

        if ($children === []) {
            return null;
        }

        return [
            'type' => self::TYPE_GROUP,
            'label' => __($label),
            'icon' => $icon,
            'url' => '#',
            'active' => self::hasActiveChild($children),
            'children' => $children,
        ];
    }

    /**
     * Titre de section (ex. "Settings").
     */
    protected static function header(string $label): array
    {
        return [
            'type' => self::TYPE_HEADER,
            'label' => __($label),
            'icon' => null,
            'url' => null,
            'active' => false,
            'children' => [],
        ];
    }

    /**
     * L'etat actif d'un groupe est toujours deduit de ses enfants.
     *
     * @param  array<int, array<string, mixed>>  $children
     */
    protected static function hasActiveChild(array $children): bool
    {
        foreach ($children as $child) {
            if ($child['active'] || self::hasActiveChild($child['children'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Supprime les entrees nulles et reindexe le tableau.
     *
     * @param  array<int, array<string, mixed>|null>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function compact(array $items): array
    {
        return array_values(array_filter($items));
    }
}
