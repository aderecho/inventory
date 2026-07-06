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
use App\Http\Controllers\EmbedTokenController;
use App\Http\Controllers\EmbedDashboardController;
use App\Http\Controllers\AcknowledgementController;
use App\Http\Controllers\ItemLocationHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::get('/register', [AuthController::class, 'register'])->name('register.store');
    Route::post('/register', [AuthController::class, 'store'])->name('store');
});

Route::get('/embed/dashboard/{token}', [EmbedDashboardController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('embed.dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'index'])->name('user.dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth'])->group(function () {

    // Update Profile
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Embed Tokens
    Route::middleware('auth')->post('/dashboard/embed-tokens', [EmbedTokenController::class, 'store']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'searchBar'])->middleware('can:view dashboard')->name('dashboard.index');

    // Item Archives
    Route::middleware('can:view archive_item')->get('/inventory/items/archive', [ItemArchivingController::class, 'index'])->name('items.archive.index');
    Route::patch('/inventory/items/{id}/restore', [ItemArchivingController::class, 'restore'])->middleware('can:restore archive_item')->name('items.restore');
    Route::delete('/inventory/items/{id}/force-delete', [ItemArchivingController::class, 'forceDelete'])->middleware('can:force delete archive_item')->name('items.forceDelete');

    // Inventory
    Route::middleware('can:view inventory')->group(function () {
        Route::get('/inventory/items', [InventoryController::class, 'InventoryItems'])->name('inventory.items');
        Route::get('/inventory/items/{id}', [InventoryController::class, 'show']);
    });
    Route::post('/inventory/acknowledgements/store', [InventoryController::class, 'InventoryAcknowledgementsStore'])->middleware('can:create acknowledgements')->name('inventory.acknowledgements.store');
    Route::middleware('can:edit inventory')->group(function () {
        Route::put('/inventory/items/update-category', [InventoryController::class, 'updateCategoryForItems'])->name('inventory.items.update-category');
        Route::put('/items/{id}', [InventoryController::class, 'update'])->name('items.update');
    });
    Route::post('/items/store', [InventoryController::class, 'store'])->middleware('can:create inventory')->name('items.store');
    Route::delete('/items/{id}', [InventoryController::class, 'destroy'])->middleware('can:delete inventory')->name('items.destroy');
    Route::middleware('can:print inventory')->group(function () {
        Route::post('/inventory/qr-pngs', [InventoryController::class, 'downloadQrPngs'])->name('inventory.qr.pngs');
        Route::post('/print/receipt', [PrintController::class, 'printReceipt'])->name('print.receipt');
    });
    Route::middleware('can:import inventory')->group(function () {
        Route::post('/convert-excel-to-csv', [InventoryController::class, 'convert']);
        Route::post('/import-csv', [InventoryController::class, 'importCsv']);
    });
    Route::get('/export-csv', [InventoryController::class, 'exportCsv'])->middleware('can:export inventory');

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'suppliers'])->middleware('can:view suppliers')->name('suppliers.index');
        Route::post('/', [SupplierController::class, 'store'])->middleware('can:create suppliers')->name('suppliers.store');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('can:edit suppliers')->name('suppliers.update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('can:delete suppliers')->name('suppliers.destroy');

        // Suppliers Archive
        Route::middleware('can:view archive_supplier')->get('/archive', [SupplierArchiveController::class, 'index'])->name('suppliers.archive.index');
        Route::patch('/{id}/archive', [SupplierArchiveController::class, 'restore'])->middleware('can:restore archive_supplier')->name('suppliers.restore');
        Route::delete('/{id}/force-delete', [SupplierArchiveController::class, 'forceDelete'])->middleware('can:force delete archive_supplier')->name('suppliers.forceDelete');
    });

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [Categories::class, 'categories'])->middleware('can:view categories')->name('categories.index');
        Route::post('/', [Categories::class, 'store'])->middleware('can:create categories')->name('categories.store');
        Route::put('/{id}', [Categories::class, 'update'])->middleware('can:edit categories')->name('categories.update');
        Route::delete('/{id}', [Categories::class, 'destroy'])->middleware('can:delete categories')->name('categories.destroy');
    });

    // Acknowledgements
    Route::prefix('acknowledgements')->group(function () {
        Route::get('/',             [AcknowledgementController::class, 'index'])->middleware('can:view acknowledgements')->name('acknowledgements.index');
        Route::get('/{id}',         [AcknowledgementController::class, 'show'])->middleware('can:show acknowledgements')->name('acknowledgements.show');
        Route::post('/upload-file', [AcknowledgementController::class, 'uploadFile'])->middleware('can:upload acknowledgements')->name('acknowledgements.upload-file');
    });

    // Item Location Histories
    // Route::prefix('item-histories')->group(function () {
    //     Route::get('/',      [ItemLocationHistoryController::class, 'index'])
    //         ->middleware('can:view item histories')
    //         ->name('item-histories.index');

    //     Route::get('/{id}',  [ItemLocationHistoryController::class, 'show'])
    //         ->middleware('can:show item histories')
    //         ->name('item-histories.show');
    // });

    // Reports
    Route::get('/report', [ReportController::class, 'searchBar'])->middleware('can:view reports')->name('reports.index');

    // User Management
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserManagementController::class, 'UserManagement'])->middleware('can:view users')->name('user_management.index');
        Route::post('/', [UserManagementController::class, 'store'])->middleware('can:create users')->name('user_management.store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('can:edit users')->name('user_management.update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->middleware('can:delete users')->name('user_management.destroy');
    });
    Route::put('/users/{user}/permissions', [RolePermissionController::class, 'updateUserPermissions'])
        ->name('user_management.permissions');

    // Roles & Permissions
    Route::middleware('can:create roles')->group(function () {
        Route::post('/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
        Route::post('/permissions', [RolePermissionController::class, 'storePermission'])->name('permissions.store');
    });
    Route::middleware('can:edit roles')->group(function () {
        Route::put('/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('roles.update');
        Route::put('/permissions/{permission}', [RolePermissionController::class, 'updatePermission'])->name('permissions.update');
    });
    Route::middleware('can:delete roles')->group(function () {
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy');
        Route::delete('/permissions/{permission}', [RolePermissionController::class, 'destroyPermission'])->name('permissions.destroy');
    });
});
