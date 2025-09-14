<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\KategoriController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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

});
