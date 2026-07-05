<?php

use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PurchaseTransactionController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SaleTransactionController;
use App\Http\Controllers\API\SupplierController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Arukas - POS System API Routes
|--------------------------------------------------------------------------
*/

// ─── PUBLIC ROUTES (no auth required) ───────────────────────────────────
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::get('/auth/google/redirect',  [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback',  [AuthController::class, 'googleCallback']);

// Midtrans webhook callback (no auth, verified by signature)
Route::post('/midtrans/callback', [PaymentController::class, 'callback']);

// ─── PROTECTED ROUTES ────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/auth/user',             [AuthController::class, 'user']);
    Route::post('/auth/profile',         [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats',               [DashboardController::class, 'stats']);
        Route::get('/chart/sales',         [DashboardController::class, 'salesChart']);
        Route::get('/chart/top-products',  [DashboardController::class, 'topProducts']);
        Route::get('/recent-transactions', [DashboardController::class, 'recentTransactions']);
    });

    // CRUD Resources
    Route::get('/categories/all', [CategoryController::class, 'all']);
    Route::apiResource('categories', CategoryController::class);

    Route::apiResource('products', ProductController::class);

    Route::get('/suppliers/all', [SupplierController::class, 'all']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::apiResource('sales', SaleTransactionController::class)->only(['index', 'store', 'show']);

    Route::apiResource('purchases', PurchaseTransactionController::class)->only(['index', 'store', 'show']);
    Route::post('/purchases/{purchaseTransaction}/receive', [PurchaseTransactionController::class, 'receive']);

    // Payment
    Route::post('/sales/{saleTransaction}/pay', [PaymentController::class, 'createPayment']);

    // Reports & Export
    Route::prefix('reports')->group(function () {
        Route::get('/sales',           [ReportController::class, 'sales']);
        Route::get('/sales/export',    [ReportController::class, 'exportSales']);
        Route::get('/products/export', [ReportController::class, 'exportProducts']);
    });

    // Activity Logs (bisa dilihat semua role)
    Route::get('/activity-logs',         [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/actions', [ActivityLogController::class, 'actions']);
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

    // ─── ADMIN ONLY ──────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});

use Illuminate\Support\Facades\Artisan;

Route::get('/setup-database', function () {
    try {
        // Jalankan perintah
        $exitCode = Artisan::call('migrate:fresh', [
            '--force' => true,
            '--seed' => true
        ]);

        // TANGKAP LOG TERMINAL ASLI
        $output = Artisan::output();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'terjadi_masalah',
            'exit_code' => $exitCode,
            'terminal_output' => $output // Ini yang akan membongkar letak errornya!
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'terminal_output' => Artisan::output()
        ]);
    }
});
