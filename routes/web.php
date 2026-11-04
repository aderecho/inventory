<?php

use App\Http\Controllers\Categories;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemArchivingController;
use App\Http\Controllers\CategoriesArchiveController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SupplierArchiveController;
use App\Http\Controllers\EmbedDashboardController;
use App\Http\Controllers\ItemApprovalController;
use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\ItemLocationHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Models\User;
use App\Http\Controllers\PropertyCardController;
use App\Http\Controllers\SamlMetadataController;
use App\Http\Controllers\SamlSpController;
use App\Http\Controllers\AuditLogsController;
use App\Http\Controllers\DisposalController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\SamlConfigurationController;
use App\Http\Controllers\UserArchiveController;
use App\Http\Controllers\DisposalPrintController;
use App\Http\Controllers\DashboardSelectionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\EmbedTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/saml2/metadata', SamlMetadataController::class)->name('saml.metadata');

Route::get('/saml2/login', [SamlSpController::class, 'redirectToIdp'])->name('saml.login');
Route::get('/saml2/acs', fn() => redirect()->route('saml.login'))->name('saml.acs.start');
Route::post('/saml2/acs', [SamlSpController::class, 'acs'])->name('saml.acs');
Route::match(['GET', 'POST'], '/saml2/logout', [SamlSpController::class, 'logout'])->name('saml.logout');
Route::get('/saml2/user-not-found', function (Request $request) {
    return Inertia::render('SamlUserNotFound', [
        'email' => $request->session()->get('saml_email'),
        'reason' => $request->session()->get('saml_reason'),
    ]);
})->name('saml.user-not-found');


Route::prefix('v1')->middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});

Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
});

Route::get('/embed/dashboard', [EmbedDashboardController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('embed.dashboard');

Route::middleware(['auth', 'check.session.timeout'])->get('/test-logout', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'message' => 'If you see this, middleware passed. Wait 1+ min and refresh this page.',
    ]);
})->name('test.logout');

Route::get('/select-dashboard', [DashboardSelectionController::class, 'show'])->name('auth.select-dashboard');
Route::post('/select-dashboard', [DashboardSelectionController::class, 'choose'])->name('auth.choose-dashboard');

// All authenticated routes with session timeout check
Route::middleware(['auth', 'check.session.timeout'])->group(function () {

    // "Switch workspace" from the settings menu.
    Route::post('/switch-dashboard', [DashboardSelectionController::class, 'switch'])
        ->name('auth.switch-dashboard');

    Route::get('/dashboard-test', [EmbedTestController::class, 'index'])
        ->name('dashboard.test');

    Route::get('/session/ping', function (Request $request) {
        return response()->json([
            'success' => true,
        ]);
    })->name('session.ping');
    // User Dashboard
    Route::get('/user/dashboard', [UserController::class, 'index'])->name('user.dashboard');

    Route::get('/student/dashboard', [StudentController::class, 'index'])
        ->middleware('role:' . User::ROLE_STUDENT)
        ->name('student.dashboard');

    // Property Cards: one ledger per article, built from its recorded history.
    Route::prefix('property-cards')->group(function () {
        Route::get('/', [PropertyCardController::class, 'index'])
            ->middleware('can:view property cards')
            ->name('property-cards.index');

        Route::get('/{item}/pdf', [PropertyCardController::class, 'pdf'])
            ->middleware('can:view property cards')
            ->name('property-cards.pdf');

        Route::get('/{item}', [PropertyCardController::class, 'show'])
            ->middleware('can:view property cards')
            ->name('property-cards.show');
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Update Profile
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'searchBar'])->middleware('can:view dashboard')->name('dashboard.index');

    Route::patch('/inventory-items/{inventory_item}/approval', [UserController::class, 'updateApproval'])
        ->name('inventory_items.approval');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{id}/items', [NotificationController::class, 'removeItems'])
        ->name('notifications.remove-items');

    // API Clients
    Route::prefix('api-clients')->group(function () {
        Route::get('/', [ApiClientController::class, 'index'])->middleware('can:view users')->name('api_clients.index');
        Route::post('/', [ApiClientController::class, 'store'])->middleware('can:create users')->name('api_clients.store');
        Route::put('/{apiClient}', [ApiClientController::class, 'update'])->middleware('can:edit users')->name('api_clients.update');
        Route::delete('/{apiClient}', [ApiClientController::class, 'destroy'])->middleware('can:delete users')->name('api_clients.destroy');
    });

    Route::prefix('audit_logs')->middleware('can:view inventory')->group(function () {
        Route::get('/', [AuditLogsController::class, 'index'])->name('audit_logs.index');
    });

    Route::prefix('saml-configurations')->middleware('can:view roles')->group(function () {
        Route::get('/', [SamlConfigurationController::class, 'index'])->name('saml_configurations.index');
        Route::post('/', [SamlConfigurationController::class, 'store'])->name('saml_configurations.store');
        Route::put('/{samlConfiguration}', [SamlConfigurationController::class, 'update'])->name('saml_configurations.update');
        Route::delete('/{samlConfiguration}', [SamlConfigurationController::class, 'destroy'])->name('saml_configurations.destroy');
    });

    // Regenerate API Key
    Route::post('/{apiClient}/regenerate', [ApiClientController::class, 'regenerate'])
        ->middleware('can:edit users')
        ->name('api_clients.regenerate');

    // Item Archives
    Route::middleware('can:view archive_item')->get('/inventory/items/archive', [ItemArchivingController::class, 'index'])->name('items.archive.index');
    Route::patch('/inventory/items/batch-restore', [ItemArchivingController::class, 'batchRestore'])->middleware('can:restore archive_item')->name('items.batch-restore');
    Route::delete('/inventory/items/batch-force-delete', [ItemArchivingController::class, 'batchForceDelete'])->middleware('can:force delete archive_item')->name('items.batch-force-delete');
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
    Route::delete('/inventory/items/batch-archive', [InventoryController::class, 'batchArchive'])->middleware('can:delete inventory')->name('items.batch-archive');
    Route::patch('/inventory/items/dispose', [InventoryController::class, 'disposeItems'])->name('items.dispose');
    Route::middleware('can:print inventory')->group(function () {
        Route::post('/inventory/qr-pngs', [InventoryController::class, 'downloadQrPngs'])->name('inventory.qr.pngs');
        Route::post('/inventory/qr-pdfs', [InventoryController::class, 'downloadQrPdfs'])->name('inventory.qr.pdfs');
        Route::post('/inventory/qr-pngs/stream', [InventoryController::class, 'streamQrPngs'])->name('inventory.qr.pngs.stream');
        Route::post('/inventory/qr-pdfs/stream', [InventoryController::class, 'streamQrPdfs'])->name('inventory.qr.pdfs.stream');
        Route::post('/print/receipt', [PrintController::class, 'printReceipt'])->name('print.receipt');
        Route::post('/print/receipt/sign', [PrintController::class, 'signReceipt'])->name('print.receipt.sign');
    });
    Route::middleware('can:import inventory')->group(function () {
        Route::post('/convert-excel-to-csv', [InventoryController::class, 'convert']);
        Route::post('/import-csv', [InventoryController::class, 'importCsv']);
        Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
        Route::get('/inventory/export/sample', [InventoryController::class, 'exportSample'])->name('inventory.export.sample');
        Route::get('/inventory/export', [InventoryController::class, 'exportExcel'])->name('inventory.export');
    });
    Route::get('/export-csv', [InventoryController::class, 'exportCsv'])->middleware('can:export inventory');

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'suppliers'])->middleware('can:view suppliers')->name('suppliers.index');
        Route::post('/', [SupplierController::class, 'store'])->middleware('can:create suppliers')->name('suppliers.store');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('can:edit suppliers')->name('suppliers.update');
        Route::delete('/batch-archive', [SupplierController::class, 'batchArchive'])->middleware('can:delete suppliers')->name('suppliers.batch-archive');
        Route::patch('/batch-restore', [SupplierArchiveController::class, 'batchRestore'])->middleware('can:restore archive_supplier')->name('suppliers.batch-restore');
        Route::delete('/batch-force-delete', [SupplierArchiveController::class, 'batchForceDelete'])->middleware('can:force delete archive_supplier')->name('suppliers.batch-force-delete');
        Route::post('/batch-add', [SupplierController::class, 'import'])->middleware('can:create suppliers')->name('suppliers.batch-add');
        Route::get('/batch-add/sample', [SupplierController::class, 'exportSample'])->middleware('can:create suppliers')->name('suppliers.batch-add.sample');
        Route::get('/export', [SupplierController::class, 'exportExcel'])->middleware('can:view suppliers')->name('suppliers.export');
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
        Route::delete('/batch-delete', [Categories::class, 'batchDelete'])->middleware('can:delete categories')->name('categories.batch-delete');
        Route::patch('/batch-restore', [CategoriesArchiveController::class, 'batchRestore'])->name('categories.batch-restore');
        Route::delete('/batch-force-delete', [CategoriesArchiveController::class, 'batchForceDelete'])->name('categories.batch-force-delete');
        Route::post('/batch-add', [Categories::class, 'import'])->middleware('can:create categories')->name('categories.batch-add');
        Route::get('/batch-add/sample', [Categories::class, 'exportSample'])->middleware('can:create categories')->name('categories.batch-add.sample');
        Route::get('/export', [Categories::class, 'exportExcel'])->middleware('can:view categories')->name('categories.export');
        Route::put('/{id}', [Categories::class, 'update'])->middleware('can:edit categories')->name('categories.update');
        Route::delete('/{id}', [Categories::class, 'destroy'])->middleware('can:delete categories')->name('categories.destroy');

        // Categories Archive
        Route::middleware('can:view categories')->get('/archive', [CategoriesArchiveController::class, 'index'])->name('categories.archive.index');
        Route::patch('/{id}/archive', [CategoriesArchiveController::class, 'restore'])->name('categories.restore');
        Route::delete('/{id}/force-delete', [CategoriesArchiveController::class, 'forceDelete'])->name('categories.forceDelete');
    });

    // Disposal
    Route::prefix('disposal')->group(function () {
        Route::get('/', [DisposalController::class, 'index'])->name('disposal.index');
        Route::delete('/batch-archive', [DisposalController::class, 'batchArchive'])
            ->middleware('can:archive disposal')
            ->name('disposal.batch-archive');
        Route::delete('/{item}/archive', [DisposalController::class, 'archive'])
            ->middleware('can:archive disposal')
            ->name('disposal.archive');

        Route::post('/print/iirup', [DisposalPrintController::class, 'printIirup'])
            ->name('disposal.print.iirup');

        Route::post('/print/iirup/below', [DisposalPrintController::class, 'printIirupBelow'])
            ->name('disposal.print.iirup.below');

        Route::post('/print/iirup/stream', [DisposalPrintController::class, 'streamIirup'])
            ->name('disposal.print.iirup.stream');

        Route::post('/print/iirup/below/stream', [DisposalPrintController::class, 'streamIirupBelow'])
            ->name('disposal.print.iirup.below.stream');

        Route::post('/print/iirup/combined/stream', [DisposalPrintController::class, 'streamCombinedIirup'])
            ->name('disposal.print.iirup.combined.stream');
    });

    // Inspection Items
    Route::prefix('inspection')->group(function () {
        Route::get('/', [InspectionController::class, 'index'])->name('inspection.index');
        Route::post('/', [InspectionController::class, 'store'])->name('inspection.store');
        Route::get('/item/{itemId}/history', [InspectionController::class, 'getItemInspectionHistory'])->name('inspection.history');
        Route::get('/stats', [InspectionController::class, 'getStats'])->name('inspection.stats');
        Route::get('/{id}', [InspectionController::class, 'show'])->name('inspection.show'); // must stay last
    });

    Route::prefix('asset-conditions')->group(function () {
        Route::get('/', [InspectionController::class, 'getConditions'])->name('asset-conditions.index');
        Route::post('/', [InspectionController::class, 'storeCondition'])->name('asset-conditions.store');
        Route::put('/asset-conditions/{assetCondition}', [InspectionController::class, 'updateCondition'])
            ->name('asset-conditions.update');
        Route::delete('/asset-conditions/{assetCondition}', [InspectionController::class, 'destroyCondition'])
            ->name('asset-conditions.destroy');
    });

    // Item Approvals: a read-only view of inventory_items.approval_status.
    Route::get('/item-approvals', [ItemApprovalController::class, 'index'])
        ->middleware('can:view item approvals')
        ->name('item-approvals.index');

    // Item Location Histories
    Route::prefix('item-histories')->group(function () {
        Route::get('/', [ItemLocationHistoryController::class, 'index'])->name('item-histories.index');
        Route::get('/{id}', [ItemLocationHistoryController::class, 'show'])->middleware('can:show item histories')->name('item-histories.show');
    });

    // Borrowings
    Route::prefix('borrowings')->group(function () {
        Route::get('/', [BorrowingController::class, 'index'])
            ->middleware('can:view borrowings')
            ->name('borrowings.index');

        Route::post('/', [BorrowingController::class, 'store'])
            ->middleware('can:borrow items')
            ->name('borrowings.store');

        Route::patch('/return-items', [BorrowingController::class, 'returnAny'])
            ->middleware('can:return items')
            ->name('borrowings.return-items');

        Route::patch('/{borrowing}/return', [BorrowingController::class, 'returnItems'])
            ->middleware('can:return items')
            ->name('borrowings.return');

        Route::get('/history', [BorrowingController::class, 'history'])
            ->middleware('can:view borrowings')
            ->name('borrowings.history');

        Route::get('/borrow-items', [BorrowingController::class, 'borrowableItems'])
            ->middleware('can:view borrowings')
            ->name('borrowings.borrow-items');

        Route::get('/staff', [BorrowingController::class, 'staff'])
            ->middleware('can:borrow items')
            ->name('borrowings.staff');

        Route::get('/students', [BorrowingController::class, 'students'])
            ->middleware('can:borrow items')
            ->name('borrowings.students');
    });

    // Reports
    Route::get('/report', [ReportController::class, 'searchBar'])->middleware('can:view reports')->name('reports.index');

    // User Management
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserManagementController::class, 'UserManagement'])->middleware('can:view users')->name('user_management.index');
        Route::post('/', [UserManagementController::class, 'store'])->middleware('can:create users')->name('user_management.store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('can:edit users')->name('user_management.update');
        Route::patch('/batch-restore', [UserArchiveController::class, 'batchRestore'])->middleware('can:restore archive_users')->name('user_management.batch-restore');
        Route::delete('/batch-force-delete', [UserArchiveController::class, 'batchForceDelete'])->middleware('can:force delete archive_users')->name('user_management.batch-force-delete');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->middleware('can:delete users')->name('user_management.destroy');

        // User Archive
        Route::middleware('can:view archive_users')->get('/archive', [UserArchiveController::class, 'index'])->name('user_management.archive.index');
        Route::patch('/{id}/archive', [UserArchiveController::class, 'restore'])->middleware('can:restore archive_users')->name('user_management.restore');
        Route::delete('/{id}/force-delete', [UserArchiveController::class, 'forceDelete'])->middleware('can:force delete archive_users')->name('user_management.forceDelete');
    });
    Route::put('/users/{user}/permissions', [RolePermissionController::class, 'updateUserPermissions'])->name('user_management.permissions');

    // Roles & Permissions
    Route::get('/roles', [RolePermissionController::class, 'rolesIndex'])
        ->middleware('can:view roles')
        ->name('roles.index');

    Route::get('/permissions', [RolePermissionController::class, 'permissionsIndex'])
        ->middleware('can:view permissions')
        ->name('permissions.index');

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
