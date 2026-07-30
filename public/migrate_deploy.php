<?php
/**
 * ============================================================
 * SCRIPT MIGRATE + STORAGE LINK + SEED LOKASI (AMAN) + CLEAR CACHE — TANPA SSH
 * ============================================================
 *
 * Untuk update: sidebar, lokasi pengambilan (dropdown per unit), foto
 * order/set (butuh storage:link agar bisa diakses lewat browser), dsb.
 *
 * TIDAK menjalankan db:seed umum (yang bisa menimpa ulang password akun
 * asli) — hanya menjalankan PickupLocationSeeder yang cuma menyentuh
 * tabel pickup_locations, aman untuk server dengan data asli.
 *
 * Tidak memakai shell_exec() — Artisan dijalankan langsung di proses PHP
 * yang sama lewat Kernel Laravel (hosting ini mematikan shell_exec).
 *
 * CARA PAKAI:
 * 1. Upload file ini ke folder public/ proyek Laravel di server
 *    (sejajar dengan index.php)
 * 2. Buka browser: https://cobafkkits.my.id/migrate_deploy.php?key=RSKEMENKES
 * 3. Periksa hasilnya — pastikan semua ✅
 * 4. HAPUS file ini segera setelah selesai!
 *
 * TAMBAHAN DATA DEMO (opsional, untuk simulasi/uji coba di web):
 * Tambahkan &seed_demo=1 di URL, mis.:
 * https://cobafkkits.my.id/migrate_deploy.php?key=RSKEMENKES&seed_demo=1
 * Ini membuat BANYAK order/alat contoh tersebar di berbagai unit & tahap
 * (pakai akun demo yang SUDAH ada, TIDAK membuat user baru). Aman dijalankan
 * berkali-kali — cuma menambah data baru, tidak menghapus/menimpa yang lama.
 *
 * PERINGATAN: Jangan biarkan file ini online lebih dari perlu!
 * ============================================================
 */

// ---- KONFIGURASI: ISI SEBELUM UPLOAD ----
define('SETUP_SECRET', 'RSKEMENKES'); // kode keamanan akses — sebaiknya ganti ke kode baru
// -----------------------------------------

$secret = $_GET['key'] ?? '';
if ($secret !== SETUP_SECRET) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Sertakan ?key=KODE_RAHASIA pada URL.</p>');
}

set_time_limit(300);
$results = [];

function run(string $label, callable $fn): void {
    global $results;
    try {
        $result = $fn();
        $results[] = ['status' => 'ok', 'label' => $label, 'msg' => $result ?? 'Selesai'];
    } catch (Throwable $e) {
        $results[] = ['status' => 'error', 'label' => $label, 'msg' => $e->getMessage()];
    }
}

// --- Boot Laravel di dalam proses PHP ini sendiri (tanpa subprocess) ---
run('Boot Laravel', function () {
    require __DIR__.'/../vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require __DIR__.'/../bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $GLOBALS['laravelApp'] = $app;

    return 'Laravel berhasil di-boot dalam mode console.';
});

if (isset($GLOBALS['laravelApp'])) {
    run('Cek Status Migrasi', function () {
        \Illuminate\Support\Facades\Artisan::call('migrate:status');
        return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    run('Database Migration', function () {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    run('Siapkan Folder Storage (buat + set izin tulis)', function () {
        $log = [];

        // storage:link menolak mengganti public/storage kalau itu SUDAH berupa
        // folder biasa (bukan symlink) — --force cuma berlaku untuk symlink lama.
        // Kalau folder itu ternyata sudah berisi file asli, JANGAN dihapus otomatis.
        $publicStorage = __DIR__.'/storage';
        if (is_dir($publicStorage) && ! is_link($publicStorage)) {
            $isEmpty = count(scandir($publicStorage)) <= 2; // hanya '.' dan '..'
            if ($isEmpty) {
                rmdir($publicStorage);
                $log[] = "public/storage folder kosong lama dihapus, siap dibuat ulang sebagai symlink.";
            } else {
                $log[] = "⚠️ public/storage berisi file dan BUKAN symlink — tidak dihapus otomatis. Cek manual lewat File Manager.";
            }
        }

        // Folder tujuan upload — dibuat lewat PHP (bukan FileZilla) supaya
        // otomatis dimiliki user yang sama dengan proses PHP-FPM, sehingga
        // pasti bisa ditulisi. Ini penyebab paling umum foto gagal tersimpan
        // tanpa ada galat sama sekali di server hasil upload manual.
        foreach (['app/public', 'app/public/orders', 'app/public/assembled-sets', 'app/public/sterilization'] as $rel) {
            $path = __DIR__.'/../storage/'.$rel;
            if (! is_dir($path)) {
                mkdir($path, 0775, true);
                $log[] = "Dibuat: storage/{$rel}";
            }
            @chmod($path, 0775);
        }

        return $log ? implode('<br>', $log) : 'Semua folder storage sudah siap.';
    });

    run('Storage Symlink (agar foto bukti/hasil steril bisa dilihat)', function () {
        \Illuminate\Support\Facades\Artisan::call('storage:link', ['--force' => true]);
        return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    run('Tes Tulis File ke Disk Public (memverifikasi upload sungguhan akan berhasil)', function () {
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path('orders/_write_test.txt');
        \Illuminate\Support\Facades\Storage::disk('public')->put('orders/_write_test.txt', 'ok');

        if (! file_exists($path)) {
            throw new \RuntimeException("File tidak ditemukan setelah ditulis di: {$path}");
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete('orders/_write_test.txt');

        return "✅ Berhasil tulis & baca file di storage/app/public/orders — upload foto seharusnya sudah bisa jalan normal.";
    });

    run('Seed Lokasi Pengambilan (dummy awal, aman)', function () {
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PickupLocationSeeder',
            '--force' => true,
        ]);
        return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    run('Clear Cache (config, route, view)', function () {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    // Opsional — cuma jalan kalau URL diberi &seed_demo=1. TIDAK membuat user baru,
    // TIDAK menghapus data lama — cuma menambah banyak order/alat contoh untuk uji coba.
    if (($_GET['seed_demo'] ?? '') === '1') {
        run('Isi Data Demo Tambahan (DemoBulkSeeder)', function () {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoBulkSeeder',
                '--force' => true,
            ]);
            return '<pre style="white-space:pre-wrap">' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
        });
    }
}

// --- RENDER HTML ---
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Migrate Deploy - SIM CSSD</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1 { color: #38bdf8; }
  .card { background: #1e293b; border-radius: 8px; padding: 1rem 1.5rem; margin: 1rem 0; border-left: 4px solid #334155; }
  .ok { border-color: #22c55e; }
  .error { border-color: #ef4444; }
  .label { font-weight: bold; margin-bottom: .5rem; }
  .ok .label::before { content: '✅ '; }
  .error .label::before { content: '❌ '; }
  .msg { color: #94a3b8; font-size: .85rem; overflow-x: auto; }
  .warning { background: #7c2d12; border-color: #f97316; color: #fed7aa; padding: 1rem; border-radius: 8px; margin-top: 2rem; }
</style>
</head>
<body>
<h1>🚀 Migrate + Seed Lokasi + Clear Cache — SIM Alat CSSD</h1>
<p>Domain: <strong>cobafkkits.my.id</strong> | Waktu: <?= date('Y-m-d H:i:s') ?></p>
<p><em>Tidak menjalankan db:seed umum (aman untuk password akun asli). Tidak memakai shell_exec().</em></p>
<?php foreach ($results as $r): ?>
<div class="card <?= $r['status'] ?>">
  <div class="label"><?= htmlspecialchars($r['label']) ?></div>
  <div class="msg"><?= $r['msg'] ?></div>
</div>
<?php endforeach; ?>
<div class="warning">
  <strong>⚠️ PENTING!</strong> Segera hapus file <code>migrate_deploy.php</code> ini dari server setelah selesai!
</div>
</body>
</html>
