<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SimulationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will be
| assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::middleware('permission:view_dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    

    // User Management routes
    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class);
    });

    // Role Management routes
    Route::middleware('permission:manage_roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });

    // Permission Management routes
    Route::middleware('permission:manage_permissions')->group(function () {
        Route::resource('permissions', PermissionController::class);
    });

    // Kategori Management routes
    Route::middleware('permission:manage_kategori')->group(function () {
        Route::resource('kategori', KategoriController::class);
    });

    // Layanan Management routes
    Route::middleware('permission:manage_layanan')->group(function () {
        Route::resource('layanan', LayananController::class);
        Route::get('layanan-export', [LayananController::class, 'export'])->name('layanan.export');
        Route::post('layanan-clear-all', [LayananController::class, 'clearAll'])->name('layanan.clear-all');
    });

    // Excel Upload routes (separate permission)
    Route::middleware('permission:upload_layanan_excel')->group(function () {
        Route::get('layanan-upload', [LayananController::class, 'showUploadForm'])->name('layanan.upload.form');
        Route::post('layanan-upload', [LayananController::class, 'uploadExcel'])->name('layanan.upload');
    });

    // Simulation routes
    Route::middleware('permission:access_simulation')->group(function () {
        Route::get('simulation', [SimulationController::class, 'index'])->name('simulation.index');
        Route::get('simulation/layanan', [SimulationController::class, 'getLayanan'])->name('simulation.layanan');
        Route::get('simulation/list', [SimulationController::class, 'list'])->name('simulation.list');
        Route::post('simulation', [SimulationController::class, 'store'])->name('simulation.store');
        Route::get('simulation/{simulation}', [SimulationController::class, 'show'])->whereNumber('simulation')->name('simulation.show');
        Route::put('simulation/{simulation}', [SimulationController::class, 'update'])->whereNumber('simulation')->name('simulation.update');
        Route::delete('simulation/{simulation}', [SimulationController::class, 'destroy'])->whereNumber('simulation')->name('simulation.destroy');
    });

    // Qty simulation tier preset routes
    // Public (for qty users) - read presets
    Route::middleware('permission:access_simulation_qty')->group(function () {
        Route::get('simulation-qty', [SimulationController::class, 'indexQty'])->name('simulation.qty');
        Route::get('simulation-qty/presets', [SimulationController::class, 'tierPresets'])->name('simulation.qty.presets');
        // qty simulation CRUD
        Route::get('simulation-qty/list', [SimulationController::class, 'listQty'])->name('simulation.qty.list');
        Route::post('simulation-qty', [SimulationController::class, 'storeQty'])->name('simulation.qty.store');
        Route::get('simulation-qty/{simulation}', [SimulationController::class, 'showQty'])->whereNumber('simulation')->name('simulation.qty.show');
        Route::put('simulation-qty/{simulation}', [SimulationController::class, 'updateQty'])->whereNumber('simulation')->name('simulation.qty.update');
        Route::delete('simulation-qty/{simulation}', [SimulationController::class, 'destroyQty'])->whereNumber('simulation')->name('simulation.qty.destroy');
    });

    // Management (separate permission) - manage single global preset
    Route::middleware('permission:manage_simulation_qty_presets')->group(function () {
        Route::get('simulation-qty/presets-page', [SimulationController::class, 'presetsPage'])->name('simulation.qty.presets.page');
        Route::post('simulation-qty/presets', [SimulationController::class, 'storeTierPreset'])->name('simulation.qty.presets.store');
        Route::put('simulation-qty/presets/{id}', [SimulationController::class, 'updateTierPreset'])->name('simulation.qty.presets.update');
        Route::delete('simulation-qty/presets/{id}', [SimulationController::class, 'destroyTierPreset'])->name('simulation.qty.presets.destroy');
    });

    // Shared read-only endpoints for both simulation modes (auth + controller-level permission check)
    Route::get('simulation/search', [SimulationController::class, 'search'])->name('simulation.search');
    Route::get('simulation/categories', [SimulationController::class, 'categories'])->name('simulation.categories');

});
