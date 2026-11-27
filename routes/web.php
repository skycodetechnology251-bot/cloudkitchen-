<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Web;
use Illuminate\Support\Facades\Route;

// Setup wizard: only until Daily Table is installed (404 afterwards). Every other page sends
// visitors here while Daily Table is not installed (RequireInstalled).
Route::middleware('before-install')->group(function () {
    Route::get('/install', [\App\Http\Controllers\InstallController::class, 'show'])->name('install.show');
    Route::post('/install', [\App\Http\Controllers\InstallController::class, 'store'])->middleware('throttle:10,10')->name('install.store');
});

// Public showcase website
Route::get('/', [Web\SiteController::class, 'home'])->name('home');
Route::get('/lunch-delivery/{zone:slug}', [Web\SiteController::class, 'area'])->name('area');
Route::get('/privacy', [Web\SiteController::class, 'page'])->defaults('page', 'privacy')->name('privacy');
Route::get('/refunds', [Web\SiteController::class, 'page'])->defaults('page', 'refunds')->name('refunds');
Route::get('/terms', [Web\SiteController::class, 'page'])->defaults('page', 'terms')->name('terms');
Route::post('/notify-me', [Web\SiteController::class, 'notifyMe'])->middleware('throttle:5,1')->name('notify-me');
Route::get('/sitemap.xml', [Web\SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Web\SiteController::class, 'robots']);

// Razorpay checkout page opened inside the mobile app's WebView
Route::get('/pay', [Web\SiteController::class, 'pay'])->name('pay');
// Customer GST invoice, opened from the app with a 30-minute signed link
Route::get('/invoice/{order}', fn (\App\Models\Order $order) => view('invoices.show', ['invoice' => $order->invoice()->firstOrFail()]))->middleware('signed')->name('invoice.public');

// Staff
Route::get('/staff/login', [Web\StaffAuthController::class, 'show'])->name('login');
Route::post('/staff/login', [Web\StaffAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/staff/logout', [Web\StaffAuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin,ops,kitchen_manager,kitchen_staff,cashier,support,finance,dietitian,doctor'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('perm:kitchen.board')->group(function () {
        Route::get('kitchen/{kitchen}', [Admin\KitchenBoardController::class, 'show'])->name('kitchen');
    });
    Route::middleware('perm:pos.use')->group(function () {
        Route::get('pos', [Admin\PosPageController::class, 'open'])->name('pos.open');
        Route::get('pos/{kitchen}', [Admin\PosPageController::class, 'show'])->name('pos');
    });
    Route::get('invoices/{invoice}', [Admin\ReportController::class, 'invoice'])->middleware('perm:orders.view')->name('invoice');

    Route::middleware('perm:zones.edit')->resource('zones', Admin\ZoneController::class)->except('show');
    Route::post('zones/{zone}/go-live', [Admin\ZoneController::class, 'goLive'])->middleware('perm:zones.golive')->name('zones.golive');
    Route::middleware('perm:zones.edit')->group(function () {
        Route::resource('branches', Admin\BranchController::class)->except(['show', 'destroy'])->parameters(['branches' => 'kitchen']);
        Route::post('branches/{kitchen}/toggle', [Admin\BranchController::class, 'toggle'])->name('branches.toggle');
        Route::get('kitchens/{any?}', fn () => redirect()->route('admin.branches.index'))->where('any', '.*');
    });
    Route::middleware('perm:menu.edit')->resource('dishes', Admin\DishController::class)->except('show');
    Route::middleware('perm:menu.publish')->group(function () {
        Route::get('menus', [Admin\MenuController::class, 'index'])->name('menus.index');
        Route::post('menus', [Admin\MenuController::class, 'store'])->name('menus.store');
        Route::post('menus/copy', [Admin\MenuController::class, 'copy'])->name('menus.copy');
    });
    Route::middleware('perm:orders.view')->group(function () {
        Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    });
    Route::post('orders/{order}/refund', [Admin\OrderController::class, 'refund'])->middleware('perm:orders.refund')->name('orders.refund');
    Route::middleware('perm:staff.manage')->resource('staff', Admin\StaffController::class)->except('show');
    Route::middleware('perm:staff.salary')->group(function () {
        Route::get('salaries', [Admin\SalaryController::class, 'index'])->name('salaries');
        Route::put('salaries/roles/{role}', [Admin\SalaryController::class, 'updateRole'])->name('salaries.role');
        Route::post('salaries/pay/{user}', [Admin\SalaryController::class, 'pay'])->name('salaries.pay');
        Route::delete('salaries/{payment}', [Admin\SalaryController::class, 'destroy'])->name('salaries.destroy');
        Route::get('salaries/{payment}/payslip', [Admin\SalaryController::class, 'payslip'])->name('salaries.payslip');
    });
    Route::middleware('perm:staff.roles')->group(function () {
        Route::get('roles', [Admin\RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [Admin\RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('perm:mkt.banners')->group(function () {
        Route::resource('banners', Admin\BannerController::class)->except('show');
        Route::resource('offers', Admin\OfferController::class)->except('show');
    });
    Route::middleware('perm:settings.manage')->group(function () {
        Route::get('system', [Admin\SystemController::class, 'index'])->name('system');
        Route::post('system/update', [Admin\SystemController::class, 'update'])->middleware('throttle:3,10')->name('system.update');
        Route::get('system/backups/{file}', [Admin\SystemController::class, 'backup'])->where('file', '[A-Za-z0-9_.\-]+')->name('system.backup');
        Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::get('branding', [Admin\BrandingController::class, 'show'])->name('branding');
        Route::post('branding', [Admin\BrandingController::class, 'update'])->middleware('throttle:20,1')->name('branding.update');
        Route::put('settings/{group}', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
    Route::middleware('perm:fin.reports')->group(function () {
        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('reports/gst.csv', [Admin\ReportController::class, 'gstCsv'])->middleware('perm:fin.export')->name('reports.gst');
    });
    Route::middleware('perm:expenses.edit')->resource('expenses', Admin\ExpenseController::class)->except('show');
});

// POS receipt (signed so it can be opened on the counter printer)
Route::get('/receipt/{order}', [Admin\PosPageController::class, 'receipt'])->middleware('auth')->name('pos.receipt');
