<?php
/**
 * ============================================================
 * SCRIPT SIMULASI DATA FINAL CSSD — TANPA SSH
 * ============================================================
 *
 * Script ini dirancang khusus untuk mereset seluruh data transaksi
 * dan memasukkan Master Data Alat (Katalog) dari file JSON beserta
 * simulasi unit, user, dan set instrumen.
 *
 * CARA PAKAI:
 * 1. Upload file ini ke folder public/
 * 2. Upload database/data/items.json dan database/seeders/FinalSimulationSeeder.php
 * 3. Buka browser: https://[domain]/simulate_deploy.php?key=RSKEMENKES
 * 4. HAPUS file ini segera setelah selesai!
 * ============================================================
 */

define('SETUP_SECRET', 'RSKEMENKES');

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

// Boot Laravel
run('Boot Laravel', function () {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $GLOBALS['laravelApp'] = $app;
    return 'Laravel berhasil di-boot.';
});

if (isset($GLOBALS['laravelApp'])) {
    run('Clear Cache', function () {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        return '<pre>' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });

    run('Seed Simulasi Final', function () {
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\FinalSimulationSeeder',
            '--force' => true,
        ]);
        return '<pre>' . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . '</pre>';
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Simulasi Data Final - SIM CSSD</title>
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
</style>
</head>
<body>
<h1>🧬 Eksekutor Simulasi Data Final — SIM Alat CSSD</h1>
<?php foreach ($results as $r): ?>
<div class="card <?= $r['status'] ?>">
  <div class="label"><?= htmlspecialchars($r['label']) ?></div>
  <div class="msg"><?= $r['msg'] ?></div>
</div>
<?php endforeach; ?>
</body>
</html>
