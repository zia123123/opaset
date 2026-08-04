<?php

// Isi routes/web.php dengan potongan berikut (gabungkan dengan route lain yang sudah ada)

use App\Http\Controllers\Admin\AssetImportController;
use App\Http\Controllers\Admin\AssetPendayagunaanImportController;
use App\Http\Controllers\Admin\AssetUsahaImportController;
use App\Http\Controllers\AssetPublicController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root: kalau sudah login lempar ke /menu, kalau belum lempar ke /login
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->to(Auth::check() ? route('menu') : route('login'));
})->name('home');

/*
|--------------------------------------------------------------------------
| Halaman menu/hub setelah login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/menu', [MenuController::class, 'index'])->name('menu');

/*
|--------------------------------------------------------------------------
| Halaman publik (tanpa login) — semua tetap bisa diakses langsung via URL
|--------------------------------------------------------------------------
*/
Route::name('public.assets.')->group(function () {
    Route::get('/dashboard', [AssetPublicController::class, 'dashboard'])->name('dashboard');
    Route::get('/peta', [AssetPublicController::class, 'map'])->name('map');
    Route::get('/peta/data', [AssetPublicController::class, 'mapData'])->name('map.data');
    Route::get('/peta-non-kd-list', [AssetPublicController::class, 'mapNonKd'])->name('map-non-kd');
    Route::get('/peta-non-kd-list/data', [AssetPublicController::class, 'mapDataNonKd'])->name('map-non-kd.data');
    Route::get('/peta-provinsi', [AssetPublicController::class, 'mapProvinsi'])->name('map-provinsi');
    Route::get('/data-aset', [AssetPublicController::class, 'index'])->name('index');
    Route::get('/data-aset/{asset}', [AssetPublicController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Auth (login/logout admin)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin (wajib login)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/assets/import', [AssetImportController::class, 'index'])->name('assets.import');
    Route::post('/assets/import', [AssetImportController::class, 'store'])->name('assets.import.store');

    Route::get('/assets/import-usaha', [AssetUsahaImportController::class, 'index'])->name('assets.import-usaha');
    Route::post('/assets/import-usaha', [AssetUsahaImportController::class, 'store'])->name('assets.import-usaha.store');
});