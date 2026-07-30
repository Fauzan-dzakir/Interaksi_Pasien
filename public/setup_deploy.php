<?php
/**
 * ============================================================
 * SCRIPT SETUP OTOMATIS LARAVEL - SHARED HOSTING (TANPA SSH)
 * ============================================================
 * 
 * CARA PAKAI:
 * 1. Upload file ini ke folder ROOT proyek Laravel di server
 *    (sejajar dengan folder app/, public/, vendor/, dll)
 * 2. Buka browser: https://cobafkkits.my.id/setup_deploy.php
 * 3. Ikuti instruksi yang muncul
 * 4. HAPUS file ini segera setelah setup selesai!
 * 
 * PERINGATAN: Jangan biarkan file ini online lebih dari perlu!
 * ============================================================
 */

// ---- KONFIGURASI: ISI SEBELUM UPLOAD ----
define('SETUP_SECRET', 'RSKEMENKES'); // kode keamanan akses
// -----------------------------------------

$secret = $_GET['key'] ?? '';
if ($secret !== SETUP_SECRET) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Sertakan ?key=KODE_RAHASIA pada URL.</p>');
}

set_time_limit(300);
$basePath = __DIR__;
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

// 1. Buat symlink storage -> public/storage (TANPA terminal!)
run('Storage Symlink', function() use ($basePath) {
    $target = $basePath . '/storage/app/public';
    $link   = $basePath . '/public/storage';
    if (is_link($link)) return 'Symlink sudah ada, dilewati.';
    if (!file_exists($target)) mkdir($target, 0755, true);
    if (symlink($target, $link)) return 'Symlink berhasil dibuat!';
    // Fallback: hardcopy method jika symlink gagal
    return 'Symlink gagal - gunakan metode manual (lihat panduan).';
});

// 2. Set permission folder storage & cache
run('Set Permissions', function() use ($basePath) {
    $dirs = [
        $basePath . '/storage',
        $basePath . '/storage/app',
        $basePath . '/storage/app/public',
        $basePath . '/storage/framework',
        $basePath . '/storage/framework/cache',
        $basePath . '/storage/framework/sessions',
        $basePath . '/storage/framework/views',
        $basePath . '/storage/logs',
        $basePath . '/bootstrap/cache',
    ];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) mkdir($dir, 0775, true);
        chmod($dir, 0775);
    }
    return 'Permission 775 berhasil di-set untuk semua folder storage.';
});

// 3. Jalankan migrate (buat tabel)
run('Database Migration', function() use ($basePath) {
    $artisan = $basePath . '/artisan';
    $output = shell_exec("php {$artisan} migrate --force 2>&1");
    if (str_contains($output, 'ERROR') || str_contains($output, 'error')) {
        throw new RuntimeException($output);
    }
    return nl2br(htmlspecialchars($output ?? 'Tidak ada output.'));
});

// 4. Jalankan seeder
run('Database Seeder', function() use ($basePath) {
    $artisan = $basePath . '/artisan';
    $output = shell_exec("php {$artisan} db:seed --force 2>&1");
    if (str_contains($output, 'ERROR') || str_contains($output, 'error')) {
        throw new RuntimeException($output);
    }
    return nl2br(htmlspecialchars($output ?? 'Tidak ada output.'));
});

// 5. Cache config, route, view untuk performa
run('Cache Optimize', function() use ($basePath) {
    $artisan = $basePath . '/artisan';
    shell_exec("php {$artisan} config:cache 2>&1");
    shell_exec("php {$artisan} route:cache 2>&1");
    shell_exec("php {$artisan} view:cache 2>&1");
    return 'Config, route, dan view berhasil di-cache.';
});

// --- RENDER HTML ---
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Setup SIM CSSD - Deployment</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1 { color: #38bdf8; }
  .card { background: #1e293b; border-radius: 8px; padding: 1rem 1.5rem; margin: 1rem 0; border-left: 4px solid #334155; }
  .ok { border-color: #22c55e; }
  .error { border-color: #ef4444; }
  .label { font-weight: bold; margin-bottom: .5rem; }
  .ok .label::before { content: '✅ '; }
  .error .label::before { content: '❌ '; }
  .msg { color: #94a3b8; font-size: .9rem; }
  .warning { background: #7c2d12; border-color: #f97316; color: #fed7aa; padding: 1rem; border-radius: 8px; margin-top: 2rem; }
</style>
</head>
<body>
<h1>🚀 Setup SIM Alat CSSD</h1>
<p>Domain: <strong>cobafkkits.my.id</strong> | Waktu: <?= date('Y-m-d H:i:s') ?></p>
<?php foreach ($results as $r): ?>
<div class="card <?= $r['status'] ?>">
  <div class="label"><?= htmlspecialchars($r['label']) ?></div>
  <div class="msg"><?= $r['msg'] ?></div>
</div>
<?php endforeach; ?>
<div class="warning">
  <strong>⚠️ PENTING!</strong> Segera hapus file <code>setup_deploy.php</code> ini dari server setelah setup selesai!
</div>
</body>
</html>
