<div>
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'Panduan Penggunaan','subtitle' => 'Alur kerja singkat per peran — buka bagian sesuai tugas Anda.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Panduan Penggunaan','subtitle' => 'Alur kerja singkat per peran — buka bagian sesuai tugas Anda.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

    <div class="card mb-5 border-sky-200 bg-sky-50/60 p-5">
        <h2 class="text-sm font-semibold text-sky-900">Istilah kode yang sering membingungkan</h2>
        <dl class="mt-2 space-y-1.5 text-sm text-sky-800">
            <div><strong class="font-mono">DO-...</strong> — nomor <em>Order</em>, dibuat unit saat mengirim alat kotor ke CSSD.</div>
            <div><strong class="font-mono">CSSD-...</strong> — kode label QR satu batch alat/set (satu kode = satu QR fisik yang ditempel).</div>
            <div><strong class="font-mono">PU-...</strong> — nomor <em>serah terima</em> (Pickup), dibuat CSSD saat menyerahkan alat steril ke unit. Ini yang muncul di notifikasi "alat steril siap diambil". Cari nomor ini di menu <strong>Distribusi → Riwayat Distribusi</strong> (CSSD) atau <strong>Penerimaan</strong> (unit).</div>
        </dl>
    </div>

    <div class="space-y-4">
        

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($role === \App\Enums\UserRole::Admin): ?>
        <div class="card overflow-hidden">
            <div class="px-5 py-4 text-sm font-semibold text-slate-900 border-b border-slate-200">
                Untuk Admin
            </div>
            <div class="px-5 py-4 text-sm text-slate-700 space-y-4">
                <div>
                    <h3 class="font-semibold text-slate-900">Master data (menu kiri: Unit, Katalog Alat, Set Alat, Lokasi Pengambilan)</h3>
                    <p class="mt-1">Data di sini <strong>tidak pernah benar-benar dihapus</strong> — hanya bisa dinonaktifkan. Ini disengaja, supaya riwayat order/alat lama yang menunjuk ke data tersebut tetap bisa dibaca saat audit. Kalau alat/unit sudah tidak dipakai, nonaktifkan saja, jangan minta dihapus dari database.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Set Alat</h3>
                    <p class="mt-1">Menentukan isi satu set (mis. "Set Bedah Minor" = 1 Gunting Mayo + 2 Klem Kocher + ...). Susunan ini dipakai otomatis oleh CSSD saat checklist kelengkapan set di pendataan, dan oleh unit saat menandai per-alat mana yang dipakai. Kalau susunan set di sini salah, checklist di kedua tempat itu ikut salah — cek dulu di sini kalau ada laporan checklist aneh.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Pengguna</h3>
                    <p class="mt-1">Tidak ada pendaftaran mandiri — semua akun dibuat Admin di sini. Password awal wajib diganti pengguna sendiri setelah login pertama.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Telusur Alat (audit)</h3>
                    <p class="mt-1">Cari satu kode label (CSSD-...) untuk melihat seluruh riwayat perpindahannya — dipakai saat ada laporan alat hilang atau selisih hitung.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Koreksi Admin</h3>
                    <p class="mt-1">Tombol ini ada di halaman detail tiap alat. Dipakai <strong>hanya</strong> untuk membetulkan salah scan petugas atau menandai alat hilang/tidak dipakai lagi — bukan jalur normal. Alasan koreksi wajib diisi dan tercatat permanen dengan tanda "Koreksi Admin" di riwayatnya, supaya jelas beda dari perpindahan alur biasa.</p>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($role === \App\Enums\UserRole::CssdStaff): ?>
        <div class="card overflow-hidden">
            <div class="px-5 py-4 text-sm font-semibold text-slate-900 border-b border-slate-200">
                Untuk Petugas CSSD
            </div>
            <div class="px-5 py-4 text-sm text-slate-700 space-y-4">
                <div>
                    <h3 class="font-semibold text-slate-900">1. Order Masuk</h3>
                    <p class="mt-1">Order berlabel <strong>CITO</strong> (merah) berarti mendesak — dahulukan. Klik <strong>"Data Sekarang"</strong>, hitung fisik isi kiriman, lalu catat per baris: pilih <strong>Per Set</strong> (kalau alat dirakit jadi satu set) atau <strong>Per Barang</strong> (alat lepasan sejenis digabung). Untuk baris Per Set, akan muncul checklist isi set — centang tiap alat yang sesuai, uncheck + beri catatan kalau ada yang tidak sesuai/hilang. Checklist ini opsional tapi sangat disarankan sebagai bukti kelengkapan. Setelah "Simpan Pendataan", label QR otomatis dibuat dan unit dapat notifikasi.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">2. Stasiun Scan</h3>
                    <p class="mt-1">Pilih tahap kerja di dropdown atas (Cuci, Kering, Cek Kebersihan, dst), lalu scan label QR (kamera HP via tombol "Buka Kamera", atau scanner fisik yang otomatis mengetik+Enter). Sistem menolak tegas kalau alat discan di tahap yang salah urutannya — itu bukan bug, itu supaya alat kotor tidak bisa "melompat" jadi steril tanpa lewat tahapnya.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">3. Checklist QC di halaman detail alat</h3>
                    <p class="mt-1">Buka detail satu alat (klik kode labelnya di mana pun), kalau statusnya persis di salah satu titik cek (Penerimaan/Dekontaminasi/Bersih-Packaging/Steril), muncul checklist QC di bagian atas. Ini dokumentasi tambahan, <strong>tidak wajib</strong> — Stasiun Scan tetap jalan normal tanpa checklist ini. Kalau ada item "tidak sesuai", alat otomatis dikembalikan ke tahap sebelumnya yang relevan (beri catatan alasannya).</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">4. Ganti Barcode</h3>
                    <p class="mt-1">Dipakai saat beberapa alat lepasan yang sudah bersih dirakit ulang jadi satu set baru — scan semua barcode lama, tentukan set/alat baru, dapat satu barcode baru. Barcode lama tidak hilang, ditandai "Barcode Diganti" dan tetap tertaut untuk riwayat.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">5. Distribusi</h3>
                    <p class="mt-1">Pilih unit tujuan, centang alat steril yang siap diserahkan, klik "Serahkan Alat" — ini membuat nomor <strong>PU-...</strong> dan unit langsung dapat notifikasi untuk konfirmasi. Kalau ada yang menanyakan nomor "PU-..." dari notifikasi tapi sudah tidak muncul di "Menunggu Konfirmasi", cari di kotak pencarian pada card <strong>Riwayat Distribusi</strong> di bawahnya — di situ semua serah terima (termasuk yang sudah dikonfirmasi) tetap tercatat.</p>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($role === \App\Enums\UserRole::Nakes): ?>
        <div class="card overflow-hidden">
            <div class="px-5 py-4 text-sm font-semibold text-slate-900 border-b border-slate-200">
                Untuk Unit / IBS (Dokter, Perawat, Nakes)
            </div>
            <div class="px-5 py-4 text-sm text-slate-700 space-y-4">
                <div>
                    <h3 class="font-semibold text-slate-900">1. Buat Order (kirim alat kotor ke CSSD)</h3>
                    <p class="mt-1">Sebelum isi form, cek dulu bagian <strong>"Alat di Unit Ini"</strong> di atas form — tandai alat mana yang sudah dipakai (kosong itu wajar kalau belum pernah ada serah terima sebelumnya). Alat di daftar ini tidak bisa dihapus, hanya diubah status pakainya. Lalu isi form: nama pengantar, jam kirim, jumlah box, dan <strong>foto kondisi alat wajib diisi</strong> minimal 1 foto. Kalau CITO, isi jam kebutuhan saja (tanpa tanggal) — sistem otomatis pakai hari ini atau besok tergantung jam berapa sekarang. Rincian isi kiriman <strong>tidak</strong> diisi di sini — itu tugas CSSD saat menghitung fisik barangnya.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">2. Order Saya</h3>
                    <p class="mt-1">Pantau status tiap order yang pernah dikirim. Rincian alat (per set/per barang, termasuk checklist isi set kalau CSSD mengisinya) baru muncul setelah CSSD selesai mendata — sebelum itu hanya terlihat statusnya saja.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">3. Penerimaan</h3>
                    <p class="mt-1">Saat notifikasi "alat steril siap diambil (nomor serah terima PU-...)" muncul, buka menu ini dan klik <strong>"Diterima"</strong> untuk konfirmasi. Di bagian bawah ada kolom untuk scan/ketik kode label saat alat mulai dipakai ("Tandai Alat Dipakai") — atau lebih mudah, tandai langsung dari daftar "Alat di Unit Ini" di halaman Buat Order.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Notifikasi</h3>
                    <p class="mt-1">Ikon lonceng di pojok bawah sidebar. Ada dua jenis: "Pendataan CSSD selesai" (rincian order Anda sudah bisa dilihat) dan "Alat steril siap/dikirim" (perlu konfirmasi penerimaan).</p>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/livewire/shared/guide.blade.php ENDPATH**/ ?>