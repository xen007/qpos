# QPOS — cartographie des routes et des permissions

Document genere depuis la table de routes (`php artisan route:list`) le 29/09/2026.

- **134 routes** au total ;
- **108 protégées** par le middleware `permission:xxx` ;
- **26 sans permission** ;
- **60 permissions distinctes**, toutes existantes et détenues par au moins un rôle.

Les ressources sont déclarées via `App\Support\PermissionRoutes::resource()`, qui applique
`_view` / `_create` / `_update` / `_delete` au suffixe correspondant de la permission.
Le parcours de caisse (`/get/products`, `/cart`, `/cart/increment`, `/cart/decrement`,
`/cart/delete`, `/cart/empty`, `/order/create`) est protégé en bloc par `sale_create`.

## Routes protégées

| Méthode | URI | Nom | Permissions | Autres middlewares |
|---|---|---|---|---|
| `GET, HEAD` | `admin` | `backend.admin.dashboard` | `dashboard_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/brands` | `backend.admin.brands.index` | `brand_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/brands` | `backend.admin.brands.store` | `brand_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/brands/create` | `backend.admin.brands.create` | `brand_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/brands/{brand}` | `backend.admin.brands.show` | `brand_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/brands/{brand}` | `backend.admin.brands.update` | `brand_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/brands/{brand}` | `backend.admin.brands.destroy` | `brand_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/brands/{brand}/edit` | `backend.admin.brands.edit` | `brand_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/cart` | `backend.admin.cart.index` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/cart` | `backend.admin.cart.store` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/cart/decrement` | `backend.admin.` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/cart/delete` | `backend.admin.` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/cart/empty` | `backend.admin.` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/cart/increment` | `backend.admin.` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/categories` | `backend.admin.categories.index` | `category_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/categories` | `backend.admin.categories.store` | `category_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/categories/create` | `backend.admin.categories.create` | `category_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/categories/{category}` | `backend.admin.categories.show` | `category_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/categories/{category}` | `backend.admin.categories.update` | `category_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/categories/{category}` | `backend.admin.categories.destroy` | `category_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/categories/{category}/edit` | `backend.admin.categories.edit` | `category_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/collection/invoice/{id}` | `backend.admin.collectionInvoice` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/create/customers` | `backend.admin.` | `customer_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/currencies` | `backend.admin.currencies.index` | `currency_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/currencies` | `backend.admin.currencies.store` | `currency_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/currencies/create` | `backend.admin.currencies.create` | `currency_create` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/currencies/default/{id}` | `backend.admin.currencies.setDefault` | `currency_set_default` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/currencies/{currency}` | `backend.admin.currencies.show` | `currency_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/currencies/{currency}` | `backend.admin.currencies.update` | `currency_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/currencies/{currency}` | `backend.admin.currencies.destroy` | `currency_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/currencies/{currency}/edit` | `backend.admin.currencies.edit` | `currency_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/customers` | `backend.admin.customers.index` | `customer_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/customers` | `backend.admin.customers.store` | `customer_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/customers/create` | `backend.admin.customers.create` | `customer_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/customers/orders/{id}` | `backend.admin.customers.orders` | `customer_sales` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/customers/{customer}` | `backend.admin.customers.show` | `customer_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/customers/{customer}` | `backend.admin.customers.update` | `customer_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/customers/{customer}` | `backend.admin.customers.destroy` | `customer_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/customers/{customer}/edit` | `backend.admin.customers.edit` | `customer_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/get/customers` | `backend.admin.` | `customer_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/get/products` | `backend.admin.getProducts` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, POST, HEAD` | `admin/import/products` | `backend.admin.products.import` | `product_import` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/inventory/report` | `backend.admin.inventory.report` | `reports_inventory` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/order/create` | `backend.admin.` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders` | `backend.admin.orders.index` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/orders` | `backend.admin.orders.store` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/create` | `backend.admin.orders.create` | `sale_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, POST, HEAD` | `admin/orders/due/collection/{id}` | `backend.admin.due.collection` | `sale_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/invoice/{id}` | `backend.admin.orders.invoice` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/pos-invoice/{id}` | `backend.admin.orders.pos-invoice` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/transactions/{id}` | `backend.admin.orders.transactions` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/{order}` | `backend.admin.orders.show` | `sale_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/orders/{order}` | `backend.admin.orders.update` | `sale_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/orders/{order}` | `backend.admin.orders.destroy` | `sale_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/orders/{order}/edit` | `backend.admin.orders.edit` | `sale_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/products` | `backend.admin.products.index` | `product_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/products` | `backend.admin.products.store` | `product_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/products/create` | `backend.admin.products.create` | `product_create` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/products/{product}` | `backend.admin.products.update` | `product_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/products/{product}` | `backend.admin.products.destroy` | `product_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/products/{product}/edit` | `backend.admin.products.edit` | `product_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/purchase` | `backend.admin.purchase.index` | `purchase_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/purchase/products/{id}` | `backend.admin.purchase.products` | `purchase_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/purchase/{purchase}` | `backend.admin.purchase.show` | `purchase_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/purchase/{purchase}` | `backend.admin.purchase.update` | `purchase_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/purchase/{purchase}` | `backend.admin.purchase.destroy` | `purchase_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/purchase/{purchase}/edit` | `backend.admin.purchase.edit` | `purchase_update` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/purchase/{purchase}/amend` | `backend.admin.purchase.amend` | `purchase_update` | Modification motivée avant réception/paiement ; boutique autorisée |
| `POST` | `admin/purchase/{purchase}/receive` | `backend.admin.purchase.receive` | `purchase_receive` | Réception partielle/totale ; boutique autorisée |
| `POST` | `admin/purchase/{purchase}/payments` | `backend.admin.purchase.pay` | `purchase_pay` | Règlement fournisseur ; devise/solde contrôlés |
| `POST` | `admin/purchase/{purchase}/payments/{payment}/reverse` | `backend.admin.purchase.payments.reverse` | `purchase_pay` | Nouvelle entrée motivée liée à l'original |
| `POST` | `admin/purchase/{purchase}/cancel` | `backend.admin.purchase.cancel` | `purchase_cancel` | Compensation de lots intacts ; règlements nets nuls |
| `GET, HEAD` | `admin/sale/report` | `backend.admin.sale.report` | `reports_sales` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/sale/summery` | `backend.admin.sale.summery` | `reports_summary` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/settings/website/general` | `backend.admin.settings.website.general` | `website_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-contacts` | `backend.admin.settings.website.contacts.update` | `contact_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-custom-css` | `backend.admin.settings.website.custom.css.update` | `custom_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-info` | `backend.admin.settings.website.info.update` | `website_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-invoice-settings` | `backend.admin.settings.website.invoice.update` | `invoice_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-notification-settings` | `backend.admin.settings.website.notification.settings.update` | `notification_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-social-links` | `backend.admin.settings.website.social.link.update` | `socials_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-style-settings` | `backend.admin.settings.website.style.settings.update` | `style_settings` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/general/update-website-status` | `backend.admin.settings.website.status.update` | `website_status_settings` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/settings/website/permissions` | `backend.admin.permissions` | `permission_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/permissions/create` | `backend.admin.permissions.store` | `role_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/settings/website/permissions/delete/{id}` | `backend.admin.permissions.delete` | `role_update` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/settings/website/permissions/update/{id}` | `backend.admin.permissions.update` | `role_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/settings/website/roles` | `backend.admin.roles` | `role_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/roles/create` | `backend.admin.roles.create` | `role_create` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/settings/website/roles/delete/{id}` | `backend.admin.roles.delete` | `role_delete` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/settings/website/roles/role-permission/{id}` | `backend.admin.update.role-permissions` | `role_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/settings/website/roles/role-wise-permissions/{id?}` | `backend.admin.role-wise-permissions` | `role_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/settings/website/roles/show/{id}` | `backend.admin.roles.show` | `role_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT` | `admin/settings/website/roles/update/{id}` | `backend.admin.roles.update` | `role_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/suppliers` | `backend.admin.suppliers.index` | `supplier_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/suppliers` | `backend.admin.suppliers.store` | `supplier_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/suppliers/create` | `backend.admin.suppliers.create` | `supplier_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/suppliers/{supplier}` | `backend.admin.suppliers.show` | `supplier_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/suppliers/{supplier}` | `backend.admin.suppliers.update` | `supplier_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/suppliers/{supplier}` | `backend.admin.suppliers.destroy` | `supplier_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/suppliers/{supplier}/edit` | `backend.admin.suppliers.edit` | `supplier_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/units` | `backend.admin.units.index` | `unit_view` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/units` | `backend.admin.units.store` | `unit_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/units/create` | `backend.admin.units.create` | `unit_create` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/units/{unit}` | `backend.admin.units.show` | `unit_view` | web, AdminMiddleware, PermissionMiddleware |
| `PUT, PATCH` | `admin/units/{unit}` | `backend.admin.units.update` | `unit_update` | web, AdminMiddleware, PermissionMiddleware |
| `DELETE` | `admin/units/{unit}` | `backend.admin.units.destroy` | `unit_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/units/{unit}/edit` | `backend.admin.units.edit` | `unit_update` | web, AdminMiddleware, PermissionMiddleware |
| `GET, HEAD` | `admin/users` | `backend.admin.users` | `user_view` | web, AdminMiddleware, PermissionMiddleware |
| `GET, POST, HEAD` | `admin/users/create` | `backend.admin.user.create` | `user_create` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/users/delete/{id}` | `backend.admin.user.delete` | `user_delete` | web, AdminMiddleware, PermissionMiddleware |
| `GET, POST, HEAD` | `admin/users/edit/{id}` | `backend.admin.user.edit` | `user_update` | web, AdminMiddleware, PermissionMiddleware |
| `POST` | `admin/users/suspend/{id}/{status}` | `backend.admin.user.suspend` | `user_suspend` | web, AdminMiddleware, PermissionMiddleware |

## Routes sans permission

| Méthode | URI | Nom | Autres middlewares |
|---|---|---|---|
| `GET, HEAD` | `/` | `frontend.home` | web |
| `GET, HEAD` | `_debugbar/assets/javascript` | `debugbar.assets.js` | DebugbarEnabled, Closure |
| `GET, HEAD` | `_debugbar/assets/stylesheets` | `debugbar.assets.css` | DebugbarEnabled, Closure |
| `DELETE` | `_debugbar/cache/{key}/{tags?}` | `debugbar.cache.delete` | DebugbarEnabled, Closure |
| `GET, HEAD` | `_debugbar/clockwork/{id}` | `debugbar.clockwork` | DebugbarEnabled, Closure |
| `GET, HEAD` | `_debugbar/open` | `debugbar.openhandler` | DebugbarEnabled, Closure |
| `POST` | `_debugbar/queries/explain` | `debugbar.queries.explain` | DebugbarEnabled, Closure |
| `POST` | `_ignition/execute-solution` | `ignition.executeSolution` | RunnableSolutionsEnabled |
| `GET, HEAD` | `_ignition/health-check` | `ignition.healthCheck` | RunnableSolutionsEnabled |
| `POST` | `_ignition/update-config` | `ignition.updateConfig` | RunnableSolutionsEnabled |
| `GET, HEAD` | `admin/products/{product}` | `backend.admin.products.show` | web, AdminMiddleware |
| `GET, HEAD` | `admin/profile` | `backend.admin.profile` | web, AdminMiddleware |
| `POST` | `admin/profile/update` | `backend.admin.profile.update` | web, AdminMiddleware |
| `POST` | `admin/purchase` | `backend.admin.purchase.store` | web, AdminMiddleware |
| `GET, HEAD` | `admin/purchase/create` | `backend.admin.purchase.create` | web, AdminMiddleware |
| `GET, HEAD` | `api/user` | `—` | api, Authenticate |
| `GET, HEAD` | `auth/google` | `auth.google` | web |
| `GET, HEAD` | `auth/google/callback` | `auth.google.handle.callback` | web |
| `GET, POST, HEAD` | `forget-password` | `forget.password` | web, ThrottleRequests |
| `POST` | `language` | `language.update` | web, ThrottleRequests |
| `GET, POST, HEAD` | `login` | `login` | web, ThrottleRequests |
| `POST` | `logout` | `logout` | web |
| `GET, POST, HEAD` | `new-password` | `new.password` | web, ThrottleRequests |
| `GET, POST, HEAD` | `password-reset` | `password.reset` | web, ThrottleRequests |
| `POST` | `resend-otp` | `resend.otp` | web, ThrottleRequests |
| `GET, HEAD` | `sanctum/csrf-cookie` | `sanctum.csrf-cookie` | web |

### Points d'attention sur les routes sans permission

Les fondations boutiques ajoutent les permissions Spatie point_of_sale_view et
point_of_sale_access, ainsi que l'alias middleware point-of-sale. Aucun endpoint
métier existant n'est encore affecté à ce middleware dans ce lot : ventes, achats et
stocks seront raccordés dans leurs phases propriétaires. La carte des 142 routes
ci-dessus est donc un instantané antérieur, et ne constitue pas une cartographie
recalculée après le sous-lot boutiques.

- Les bascules de langue (`language.update`) et la déconnexion (`logout`) sont ouvertes par nature ;
  l'authentification (`login`) aussi.
- `products.show` est volontairement sans permission statique : `ProductController::show` ne
  contrôle rien, comportement conservé tel quel depuis l'origine.
- `purchase.create` et `purchase.store` reçoivent une autorisation **dynamique** dans le
  contrôleur (`purchase_update` si la requête porte un `purchase_id`, `purchase_create` sinon),
  car la même URL sert la création et la modification.
