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
use App\Http\Controllers\SupplierArchiveController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::get('/register', [AuthController::class, 'register'])->name('register.store');
    Route::post('/register', [AuthController::class, 'store'])->name('store');
});

Route::middleware('auth')->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'index'])->name('user.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'searchBar'])->name('dashboard.index');

    // Item Archives
    Route::get('/inventory/items/archive', [ItemArchivingController::class, 'index'])->name('items.archive.index');
    Route::patch('/inventory/items/{id}/restore', [ItemArchivingController::class, 'restore'])->name('items.restore');
    Route::delete('/inventory/items/{id}/force-delete', [ItemArchivingController::class, 'forceDelete'])->name('items.forceDelete');

    // Inventory
    Route::get('/inventory/items', [InventoryController::class, 'InventoryItems'])->name('inventory.items');
    Route::get('/inventory/items/{id}', [InventoryController::class, 'show']);
    Route::post('/inventory/acknowledgements/store', [InventoryController::class, 'InventoryAcknowledgementsStore'])->name('inventory.acknowledgements.store');
    Route::put('/inventory/items/update-category', [InventoryController::class, 'updateCategoryForItems'])->name('inventory.items.update-category');
    Route::put('/items/{id}', [InventoryController::class, 'update'])->name('items.update');
    Route::post('/items/store', [InventoryController::class, 'store'])->name('items.store');
    Route::delete('/items/{id}', [InventoryController::class, 'destroy'])->name('items.destroy');
    Route::post('/inventory/qr-pngs', [InventoryController::class, 'downloadQrPngs'])->name('inventory.qr.pngs');
    Route::post('/print/receipt', [PrintController::class, 'printReceipt'])->name('print.receipt');
    Route::post('/convert-excel-to-csv', [InventoryController::class, 'convert']);
    Route::post('/import-csv', [InventoryController::class, 'importCsv']);
    Route::get('/export-csv', [InventoryController::class, 'exportCsv']);

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'suppliers'])->name('suppliers.index');
        Route::post('/', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        // Suppliers Archive
        Route::get('/archive', [SupplierArchiveController::class, 'index'])->name('suppliers.archive.index');
        Route::patch('/{id}/archive', [SupplierArchiveController::class, 'restore'])->name('suppliers.restore');
        Route::delete('/{id}/force-delete', [SupplierArchiveController::class, 'forceDelete'])->name('suppliers.forceDelete');
    });

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [Categories::class, 'categories'])->name('categories.index');
        Route::post('/', [Categories::class, 'store'])->name('categories.store');
        Route::put('/{id}', [Categories::class, 'update'])->name('categories.update');
        Route::delete('/{id}', [Categories::class, 'destroy'])->name('categories.destroy');
    });

    // Reports
    Route::get('/report', [ReportController::class, 'searchBar'])->name('reports.index');

    // User Management
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserManagementController::class, 'UserManagement'])->name('user_management.index');
        Route::post('/', [UserManagementController::class, 'store'])->name('user_management.store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('user_management.update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('user_management.destroy');
    });
    Route::put('/users/{user}/permissions', [RolePermissionController::class, 'updateUserPermissions'])->name('user_management.permissions');

    // Roles & Permissions
    Route::post('/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
    Route::post('/permissions', [RolePermissionController::class, 'storePermission'])->name('permissions.store');
    Route::put('/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('roles.update');
    Route::put('/permissions/{permission}', [RolePermissionController::class, 'updatePermission'])->name('permissions.update');
    Route::delete('/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy');
    Route::delete('/permissions/{permission}', [RolePermissionController::class, 'destroyPermission'])->name('permissions.destroy');
});