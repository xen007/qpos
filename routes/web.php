<?php

use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\Pos\CartController;
use App\Http\Controllers\Backend\Product\ProductController;
use App\Http\Controllers\Backend\Report\ReportController;
use App\Http\Controllers\Backend\SupplierController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Backend\Product\CategoryController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\RolePermission\PermissionController;
use App\Http\Controllers\Backend\Pos\OrderController;
use App\Http\Controllers\Backend\Product\BrandController;
use App\Http\Controllers\Backend\Product\PurchaseController;
use App\Http\Controllers\Backend\RolePermission\RoleController;
use App\Http\Controllers\Backend\Product\UnitController;
use App\Http\Controllers\Backend\UserManagementController;
use App\Http\Controllers\Backend\WebsiteSettingController;
use App\Support\PermissionRoutes;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ====================== FRONTEND ======================

// homepage
Route::get('/', function () {
    return to_route('login');
})->name('frontend.home');

Route::post('/language', [LanguageController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('language.update');

//authentication
Route::match(['get', 'post'], 'login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], 'forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:5,1')->name('forget.password');
Route::match(['get', 'post'], 'new-password', [AuthController::class, 'newPassword'])->middleware('throttle:5,1')->name('new.password');
Route::match(['get', 'post'], 'password-reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.reset');
Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1')->name('resend.otp');

// google auth
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.handle.callback');

// ====================== /FRONTEND =====================

// ====================== BACKEND =======================

Route::prefix('admin')->as('backend.admin.')->middleware(['admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:dashboard_view');
    PermissionRoutes::resource('brands', BrandController::class, 'brand');
    PermissionRoutes::resource('orders', OrderController::class, [
        'index' => 'sale_view',
        'create' => 'sale_create',
        'store' => 'sale_create',
        'show' => 'sale_view',
        'edit' => 'sale_update',
        'update' => 'sale_update',
        'destroy' => 'sale_delete',
    ]);
    PermissionRoutes::resource('purchase', PurchaseController::class, [
        'index' => 'purchase_view',
        // create et store verifient purchase_create OU purchase_update selon la
        // requete : ces deux controles restent dans le controleur.
        'create' => null,
        'store' => null,
        'show' => 'purchase_view',
        'edit' => 'purchase_update',
        'update' => 'purchase_update',
        'destroy' => 'purchase_delete',
    ]);
    PermissionRoutes::resource('suppliers', SupplierController::class, 'supplier');
    PermissionRoutes::resource('customers', CustomerController::class, 'customer');
    PermissionRoutes::resource('products', ProductController::class, [
        'index' => 'product_view',
        'create' => 'product_create',
        'store' => 'product_create',
        // Aucune verification dans ProductController::show : comportement conserve.
        'show' => null,
        'edit' => 'product_update',
        'update' => 'product_update',
        'destroy' => 'product_delete',
    ]);
    PermissionRoutes::resource('units', UnitController::class, 'unit');
    PermissionRoutes::resource('currencies', CurrencyController::class, 'currency');
    Route::match(['get', 'post'], 'import/products', [ProductController::class,'import'])->name('products.import')->middleware('permission:product_import');
    Route::post('currencies/default/{id}', [CurrencyController::class, 'setDefault'])->name('currencies.setDefault')->middleware('permission:currency_set_default');
    Route::get('customers/orders/{id}', [CustomerController::class, 'orders'])->name('customers.orders')->middleware('permission:customer_sales');
    Route::get('purchase/products/{id}', [PurchaseController::class, 'purchaseProducts'])->name('purchase.products')->middleware('permission:purchase_view');
    Route::get('orders/invoice/{id}', [OrderController::class,'invoice'])->name('orders.invoice')->middleware('permission:sale_view');
    Route::get('orders/pos-invoice/{id}', [OrderController::class, 'posInvoice'])->name('orders.pos-invoice')->middleware('permission:sale_view');
    Route::get('orders/transactions/{id}', [OrderController::class, 'transactions'])->name('orders.transactions')->middleware('permission:sale_view');
    Route::match(['get', 'post'], 'orders/due/collection/{id}', [OrderController::class, 'collection'])->name('due.collection')->middleware('permission:sale_update');
    Route::get('collection/invoice/{id}', [OrderController::class, 'collectionInvoice'])->name('collectionInvoice')->middleware('permission:sale_view');
    PermissionRoutes::resource('categories', CategoryController::class, 'category');
    //start report

    Route::get('/sale/summery', [ReportController::class, 'saleSummery'])->name('sale.summery')->middleware('permission:reports_summary');
    Route::get('/sale/report', [ReportController::class, 'saleReport'])->name('sale.report')->middleware('permission:reports_sales');
    Route::get('/inventory/report', [ReportController::class, 'inventoryReport'])->name('inventory.report')->middleware('permission:reports_inventory');
    //end report
    // start pos
    // Le parcours de vente (caisse) est protege en bloc : sans sale_create, ces
    // endpoints n'ont pas de sens. Aucun changement d'acces aujourd'hui, les trois
    // roles detenant sale_create.
    Route::middleware('permission:sale_create')->group(function () {
        Route::get('/get/products', [CartController::class, 'getProducts'])->name('getProducts');
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::put('/cart/increment', [CartController::class, 'increment']);
        Route::put('/cart/decrement', [CartController::class, 'decrement']);
        Route::put('/cart/delete', [CartController::class, 'delete']);
        Route::put('/cart/empty', [CartController::class, 'empty']);
        Route::put('/order/create', [OrderController::class, 'store']);
    });
    Route::get('/get/customers',[CustomerController::class,'getCustomers'])->middleware('permission:customer_view');
    Route::post('/create/customers', [CustomerController::class, 'store'])->middleware('permission:customer_create');
    //end pos
    Route::get('profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('profile/update', [AuthController::class, 'update'])->name('profile.update');

    // user management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('users')->middleware('permission:user_view');
        Route::post('suspend/{id}/{status}', [UserManagementController::class, 'suspend'])->name('user.suspend')->middleware('permission:user_suspend');
        Route::match(['get', 'post'], 'create', [UserManagementController::class, 'create'])->name('user.create')->middleware('permission:user_create');
        Route::match(['get', 'post'], 'edit/{id}', [UserManagementController::class, 'edit'])->name('user.edit')->middleware('permission:user_update');
        Route::post('delete/{id}', [UserManagementController::class, 'delete'])->name('user.delete')->middleware('permission:user_delete');
    });

    // settings
    Route::prefix('settings')->group(function () {
        // website settings
        Route::prefix('website')->group(function () {
            Route::controller(WebsiteSettingController::class)->prefix('general')->group(function () {
                Route::get('/', 'websiteGeneral')->name('settings.website.general')->middleware('permission:website_settings');
                Route::post('update-info', 'websiteInfoUpdate')->name('settings.website.info.update')->middleware('permission:website_settings');
                Route::post('update-contacts', 'websiteContactsUpdate')->name('settings.website.contacts.update')->middleware('permission:contact_settings');
                Route::post('update-social-links', 'websiteSocialLinkUpdate')->name('settings.website.social.link.update')->middleware('permission:socials_settings');
                Route::post('update-style-settings', 'websiteStyleSettingsUpdate')->name('settings.website.style.settings.update')->middleware('permission:style_settings');
                Route::post('update-custom-css', 'websiteCustomCssUpdate')->name('settings.website.custom.css.update')->middleware('permission:custom_settings');
                Route::post('update-notification-settings', 'websiteNotificationSettingsUpdate')->name('settings.website.notification.settings.update')->middleware('permission:notification_settings');
                Route::post('update-website-status', 'websiteStatusUpdate')->name('settings.website.status.update')->middleware('permission:website_status_settings');

                Route::post('update-invoice-settings', 'websiteInvoiceUpdate')->name('settings.website.invoice.update')->middleware('permission:invoice_settings');
            });

            Route::controller(RoleController::class)->prefix('roles')->group(function () {
                Route::get('/', 'index')->name('roles')->middleware('permission:role_view');
                Route::post('create', 'store')->name('roles.create')->middleware('permission:role_create');
                Route::get('show/{id}', 'show')->name('roles.show')->middleware('permission:role_view');
                Route::put('update/{id}', 'update')->name('roles.update')->middleware('permission:role_update');
                Route::delete('delete/{id}', 'destroy')->name('roles.delete')->middleware('permission:role_delete');
                Route::post('role-permission/{id}', 'updatePermission')->name('update.role-permissions')->middleware('permission:role_update');
                Route::get('role-wise-permissions/{id?}', 'roleWisePermissions')->name('role-wise-permissions')->middleware('permission:role_view');
            });

            Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
                Route::get('/', 'index')->name('permissions')->middleware('permission:permission_view');
                Route::post('create', 'store')->name('permissions.store')->middleware('permission:role_update');
                // Route::get('show/{id}', 'show')->name('roles.show');
                Route::put('update/{id}', 'update')->name('permissions.update')->middleware('permission:role_update');
                Route::delete('delete/{id}', 'destroy')->name('permissions.delete')->middleware('permission:role_update');
            });
        });
    });
});

// ====================== /BACKEND ======================
