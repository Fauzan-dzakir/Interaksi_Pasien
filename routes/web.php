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
| Halaman detail & riwayat satu alat — dibuka semua peran, tapi isinya
| dibatasi ItemBatchPolicy (Nakes hanya boleh melihat alat unitnya sendiri).
*/
Route::middleware('auth')->group(function () {
    Route::get('/alat/{batch}', Shared\BatchShow::class)->name('batches.show');
    Route::get('/notifikasi', Shared\NotificationCenter::class)->name('notifications');
    Route::get('/panduan', Shared\Guide::class)->name('guide');
});

/*
| Area Admin — manajemen user, master data, penelusuran audit & koreksi.
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('dashboard');
    Route::get('/unit', Admin\UnitManager::class)->name('units');
    Route::get('/alat', Admin\ItemManager::class)->name('items');
    Route::get('/set-alat', Admin\InstrumentSetManager::class)->name('instrument-sets');
    Route::get('/lokasi', Admin\PickupLocationManager::class)->name('pickup-locations');
    Route::get('/pengguna', Admin\UserManager::class)->name('users');
    Route::get('/telusur', Admin\AuditSearch::class)->name('audit');
});

/*
| Area CSSD — Admin ikut diberi akses karena berwenang mengoreksi human error.
*/
Route::middleware(['auth', 'role:cssd_staff,admin'])->prefix('cssd')->name('cssd.')->group(function () {
    Route::get('/', Cssd\Dashboard::class)->name('dashboard');
    Route::get('/order', Cssd\OrderQueue::class)->name('orders');
    Route::get('/order/{order}', Cssd\OrderIntake::class)->name('orders.show');
    Route::get('/scan', Cssd\ScanStation::class)->name('scan');
    Route::get('/label', Cssd\LabelPrint::class)->name('labels');
    Route::get('/ganti-barcode', Cssd\BarcodeReplacement::class)->name('barcode-replacement');
    Route::get('/distribusi', Cssd\Distribution::class)->name('distribution');
});

/*
| Area Unit (Dokter/Perawat/Nakes) — seluruh data ter-scope ke unit user.
*/
Route::middleware(['auth', 'role:nakes'])->prefix('unit')->name('unit.')->group(function () {
    Route::get('/', Unit\Dashboard::class)->name('dashboard');
    Route::get('/order', Unit\OrderList::class)->name('orders');
    Route::get('/order/buat', Unit\OrderCreate::class)->name('orders.create');
    Route::get('/order/{order}', Unit\OrderShow::class)->name('orders.show');
    Route::get('/penerimaan', Unit\PickupInbox::class)->name('pickups');
    Route::get('/scan-terima', Unit\ReceiptScan::class)->name('receipt-scan');
});
