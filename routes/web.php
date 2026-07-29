<?php

use App\Livewire\Admin;
use App\Livewire\Auth\Login;
use App\Livewire\Cssd;
use App\Livewire\Shared;
use App\Livewire\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->role->homeRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/*
| Halaman lintas peran, isinya dibatasi Policy (Nakes hanya alat unitnya sendiri).
*/
Route::middleware('auth')->group(function () {
    Route::get('/aset/{asset}', Shared\AssetShow::class)->name('assets.show');
    Route::get('/notifikasi', Shared\NotificationCenter::class)->name('notifications');
    Route::get('/panduan', Shared\Guide::class)->name('guide');
});

/*
| Area Admin.
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('dashboard');
    Route::get('/unit', Admin\UnitManager::class)->name('units');
    Route::get('/alat', Admin\ItemManager::class)->name('items');
    Route::get('/set-alat', Admin\InstrumentSetManager::class)->name('instrument-sets');
    Route::get('/pengguna', Admin\UserManager::class)->name('users');
    Route::get('/telusur', Admin\AuditSearch::class)->name('audit');
    Route::get('/set-tidak-lengkap', Admin\IncompleteSets::class)->name('incomplete-sets');
});

/*
| Area CSSD, Admin ikut diberi akses karena berwenang mengoreksi human error.
*/
Route::middleware(['auth', 'role:cssd_staff,admin'])->prefix('cssd')->name('cssd.')->group(function () {
    Route::get('/', Cssd\Dashboard::class)->name('dashboard');
    Route::get('/order', Cssd\OrderQueue::class)->name('orders');
    Route::get('/order/{order}', Cssd\OrderPrepare::class)->name('orders.show');
    Route::get('/kiriman', Cssd\ReturnInbox::class)->name('returns');
    Route::get('/scan', Cssd\ScanStation::class)->name('scan');
    Route::get('/barcode-baru', Cssd\NewBarcodeScan::class)->name('new-barcode');
    Route::get('/barcode', Cssd\BarcodeStudio::class)->name('barcodes');
    Route::get('/stok', Cssd\StockManager::class)->name('stock');
    Route::get('/batch', Cssd\BatchManager::class)->name('batches');
    Route::get('/laporan', Cssd\SterilizationReports::class)->name('reports');
    Route::get('/laporan/{record}/pdf', [Cssd\ReportPdfController::class, 'show'])->name('reports.pdf');
});

/*
| Area Unit (Dokter/Perawat/Nakes), seluruh data ter-scope ke unit user.
*/
Route::middleware(['auth', 'role:nakes'])->prefix('unit')->name('unit.')->group(function () {
    Route::get('/', Unit\Dashboard::class)->name('dashboard');
    Route::get('/progress', Unit\WashProgress::class)->name('progress');
    Route::get('/order', Unit\OrderList::class)->name('orders');
    Route::get('/order/buat', Unit\OrderCreate::class)->name('orders.create');
    Route::get('/order/{order}', Unit\OrderShow::class)->name('orders.show');
    Route::get('/pemakaian', Unit\UsageScan::class)->name('usage');
});
