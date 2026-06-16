<?php

use App\Http\Controllers\Categories;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemArchivingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);

    Route::get('/register', [AuthController::class, 'register'])->name('register.store');
    Route::post('/register', [AuthController::class, 'store'])->name('store');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'searchBar'])->name('dashboard.index');

    // Inventory
    Route::get('/inventory/items', [InventoryController::class, 'InventoryItems'])->middleware('can:view inventory')->name('inventory.items');
    Route::post('/inventory/acknowledgements/store', [InventoryController::class, 'InventoryAcknowledgementsStore'])->middleware('can:create acknowledgements')->name('inventory.acknowledgements.store');
    Route::put('/inventory/items/update-category', [InventoryController::class, 'updateCategoryForItems'])->middleware('can:edit inventory')->name('inventory.items.update-category');
    Route::post('/items/store', [InventoryController::class, 'store'])->middleware('can:create inventory')->name('items.store');
    Route::put('/items/{id}', [InventoryController::class, 'update'])->middleware('can:edit inventory')->name('items.update');
    Route::delete('/items/{id}', [InventoryController::class, 'destroy'])->middleware('can:delete inventory')->name('items.destroy');
    Route::get('/inventory/items/{id}', [InventoryController::class, 'show'])->middleware('can:view inventory');
    Route::post('/inventory/qr-pngs', [InventoryController::class, 'downloadQrPngs'])->middleware('can:print inventory')->name('inventory.qr.pngs');
    Route::post('/print/receipt', [PrintController::class, 'printReceipt'])->middleware('can:print inventory')->name('print.receipt');
    Route::post('/convert-excel-to-csv', [InventoryController::class, 'convert'])->middleware('can:import inventory');
    Route::post('/import-csv', [InventoryController::class, 'importCsv'])->middleware('can:import inventory');
    Route::get('/export-csv', [InventoryController::class, 'exportCsv'])->middleware('can:export inventory');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'suppliers'])->middleware('can:view suppliers')->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('can:create suppliers')->name('suppliers.store');
    Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->middleware('can:edit suppliers')->name('suppliers.update');
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->middleware('can:delete suppliers')->name('suppliers.destroy');

    // Categories
    Route::get('/categories', [Categories::class, 'categories'])->middleware('can:view categories')->name('categories.index');
    Route::post('/categories', [Categories::class, 'store'])->middleware('can:create categories')->name('categories.store');
    Route::put('/categories{id}', [Categories::class, 'update'])->middleware('can:edit categories')->name('categories.update');
    Route::delete('/categories{id}', [Categories::class, 'destroy'])->middleware('can:delete categories')->name('categories.destroy');

    // Reports
    Route::get('/report', [ReportController::class, 'searchBar'])->middleware('can:view reports')->name('reports.index');

    // Item Archiving
    Route::get('/item_archiving', [ItemArchivingController::class, 'index'])->middleware('can:view archive')->name('item_archiving.index');
    Route::patch('/inventory/items/{id}/restore', [ItemArchivingController::class, 'restore'])->middleware('can:restore archive')->name('items.restore');
    Route::delete('/inventory/items/{id}/force-delete', [ItemArchivingController::class, 'forceDelete'])->middleware('can:force delete archive')->name('items.forceDelete');

    // User Management
    Route::get('/user-management', [UserManagementController::class, 'UserManagement'])->middleware('can:view users')->name('user_management.index');
    Route::post('/user-management', [UserManagementController::class, 'store'])->middleware('can:create users')->name('user_management.store');
    Route::put('/user-management/{user}', [UserManagementController::class, 'update'])->middleware('can:edit users')->name('user_management.update');
    Route::delete('/user-management/{user}', [UserManagementController::class, 'destroy'])->middleware('can:delete users')->name('user_management.destroy');

    // Roles & Permissions
    Route::post('/roles', [RolePermissionController::class, 'storeRole'])->middleware('can:create roles')->name('roles.store');
    Route::put('/roles/{role}', [RolePermissionController::class, 'updateRole'])->middleware('can:edit roles')->name('roles.update');
    Route::delete('/roles/{role}', [RolePermissionController::class, 'destroyRole'])->middleware('can:delete roles')->name('roles.destroy');

    Route::post('/permissions', [RolePermissionController::class, 'storePermission'])->middleware('can:create roles')->name('permissions.store');
    Route::put('/permissions/{permission}', [RolePermissionController::class, 'updatePermission'])->middleware('can:edit roles')->name('permissions.update');
    Route::delete('/permissions/{permission}', [RolePermissionController::class, 'destroyPermission'])->middleware('can:delete roles')->name('permissions.destroy');
});
