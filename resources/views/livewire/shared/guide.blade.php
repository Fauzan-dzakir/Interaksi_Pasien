<div>
    {{-- Hero — gradasi warna brand, judul besar + subjudul, senada konsep "Guides"
         ala Embroker tapi pakai warna teal aplikasi ini. --}}
    <div class="rounded-2xl bg-linear-to-br from-teal-600 to-teal-800 px-6 py-10 text-white sm:px-10 sm:py-14">
        <h1 class="text-2xl font-bold sm:text-3xl">Panduan Penggunaan</h1>
        <p class="mt-2 max-w-xl text-sm text-teal-50 sm:text-base">
            Penjelasan singkat tiap fitur sesuai peran Anda — supaya tidak perlu bertanya manual/WA tim IT
            untuk hal-hal dasar alur kerja sistem ini.
        </p>
    </div>

    <div class="mt-5 card border-sky-200 bg-sky-50/60 p-5">
        <h2 class="text-sm font-semibold text-sky-900">Istilah kode yang sering membingungkan</h2>
        <dl class="mt-2 space-y-1.5 text-sm text-sky-800">
            <div><strong class="font-mono">DO-...</strong> — nomor <em>Order</em>, dibuat unit saat mengirim alat kotor ke CSSD.</div>
            <div><strong class="font-mono">CSSD-...</strong> — kode label QR satu batch alat/set (satu kode = satu QR fisik yang ditempel).</div>
            <div><strong class="font-mono">PU-...</strong> — nomor <em>serah terima</em> (Pickup), dibuat CSSD saat menyerahkan alat steril ke unit. Ini yang muncul di notifikasi "alat steril siap diambil". Cari nomor ini di menu <strong>Distribusi → Riwayat Distribusi</strong> (CSSD) atau <strong>Penerimaan</strong> (unit).</div>
        </dl>
    </div>

    {{-- Hanya panduan sesuai peran akun yang login yang ditampilkan — akun unit
         tidak perlu melihat panduan Admin/CSSD yang tidak relevan untuk mereka. --}}

    {{-- ================= ADMIN ================= --}}
    @if ($role === \App\Enums\UserRole::Admin)
        @php
            $topics = [
                ['id' => 'admin-master', 'icon' => '🗂️', 'title' => 'Master Data'],
                ['id' => 'admin-set', 'icon' => '🧰', 'title' => 'Set Alat'],
                ['id' => 'admin-user', 'icon' => '👤', 'title' => 'Pengguna'],
                ['id' => 'admin-audit', 'icon' => '🔍', 'title' => 'Telusur Alat (Audit)'],
                ['id' => 'admin-override', 'icon' => '🛠️', 'title' => 'Koreksi Admin'],
            ];
        @endphp

        <p class="mb-3 mt-6 text-sm font-semibold text-slate-900">Untuk Admin — pilih topik</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($topics as $t)
                <a href="#{{ $t['id'] }}" class="card flex items-center gap-3 p-4 transition hover:border-teal-300 hover:shadow">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-xl">{{ $t['icon'] }}</span>
                    <span class="text-sm font-medium text-slate-800">{{ $t['title'] }}</span>
                </a>
            @endforeach
        </div>

        <div id="admin-master" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🗂️ Master Data</div>
            <div class="space-y-1 px-5 py-4 text-sm text-slate-700">
                <p>Menu kiri: Unit, Katalog Alat, Set Alat, Lokasi Pengambilan.</p>
                <p>Data di sini <strong>tidak pernah benar-benar dihapus</strong> — hanya bisa dinonaktifkan. Ini disengaja, supaya riwayat order/alat lama yang menunjuk ke data tersebut tetap bisa dibaca saat audit. Kalau alat/unit sudah tidak dipakai, nonaktifkan saja, jangan minta dihapus dari database.</p>
                <p>Di <strong>Katalog Alat</strong> juga ada "Sensitivitas Bahan" (tahan panas&air / sensitif panas / sensitif panas&air) dan "Metode Sterilisasi Default" (Autoclave/Plasma H2O2/Gas EO) — pastikan tiap alat sudah diberi metode yang benar, karena sistem menghitung masa kedaluwarsa steril otomatis dari sini (Autoclave & Plasma H2O2: 6 bulan, Gas EO: 12 bulan).</p>
            </div>
        </div>

        <div id="admin-set" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🧰 Set Alat</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Menentukan isi satu set (mis. "Set Bedah Minor" = 1 Gunting Mayo + 2 Klem Kocher + ...). Susunan ini dipakai otomatis oleh CSSD saat checklist kelengkapan set di pendataan, dan oleh unit saat menandai per-alat mana yang dipakai. Kalau susunan set di sini salah, checklist di kedua tempat itu ikut salah — cek dulu di sini kalau ada laporan checklist aneh.</p>
            </div>
        </div>

        <div id="admin-user" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">👤 Pengguna</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Tidak ada pendaftaran mandiri — semua akun dibuat Admin di sini. Password awal wajib diganti pengguna sendiri setelah login pertama.</p>
            </div>
        </div>

        <div id="admin-audit" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🔍 Telusur Alat (Audit)</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Cari satu kode label (CSSD-...), nama alat, zona, atau status untuk melihat seluruh riwayat perpindahannya — dipakai saat ada laporan alat hilang atau selisih hitung. Dropdown filternya bisa diketik untuk mencari cepat.</p>
            </div>
        </div>

        <div id="admin-override" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🛠️ Koreksi Admin</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Tombol ini ada di halaman detail tiap alat. Dipakai <strong>hanya</strong> untuk membetulkan salah scan petugas atau menandai alat hilang/tidak dipakai lagi — bukan jalur normal. Alasan koreksi wajib diisi dan tercatat permanen dengan tanda "Koreksi Admin" di riwayatnya. Kalau alat ditandai hilang/tidak dipakai lagi, unit asal otomatis dapat notifikasi dan status order ikut berubah jadi "Ada Kendala" supaya tidak terlihat seolah baik-baik saja.</p>
            </div>
        </div>
    @endif

    {{-- ================= CSSD ================= --}}
    @if ($role === \App\Enums\UserRole::CssdStaff)
        @php
            $topics = [
                ['id' => 'cssd-orders', 'icon' => '📦', 'title' => 'Order Masuk'],
                ['id' => 'cssd-dashboard', 'icon' => '📊', 'title' => 'Dashboard & Pemindahan Massal'],
                ['id' => 'cssd-scan', 'icon' => '📷', 'title' => 'Stasiun Scan'],
                ['id' => 'cssd-qc', 'icon' => '✅', 'title' => 'Checklist QC'],
                ['id' => 'cssd-replace', 'icon' => '🔄', 'title' => 'Ganti Barcode'],
                ['id' => 'cssd-distribution', 'icon' => '🚚', 'title' => 'Distribusi'],
            ];
        @endphp

        <p class="mb-3 mt-6 text-sm font-semibold text-slate-900">Untuk Petugas CSSD — pilih topik</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($topics as $t)
                <a href="#{{ $t['id'] }}" class="card flex items-center gap-3 p-4 transition hover:border-teal-300 hover:shadow">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-xl">{{ $t['icon'] }}</span>
                    <span class="text-sm font-medium text-slate-800">{{ $t['title'] }}</span>
                </a>
            @endforeach
        </div>

        <div id="cssd-orders" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📦 1. Order Masuk</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Order berlabel <strong>CITO</strong> (merah) berarti mendesak — dahulukan. Klik <strong>"Data Sekarang"</strong>, hitung fisik isi kiriman (jumlah per jenis, bukan per barcode — alat kotor belum bisa dibedakan satu-satu), lalu catat per baris: pilih <strong>Per Set</strong> atau <strong>Per Barang</strong>. Untuk baris Per Set, muncul checklist isi set — centang tiap alat yang sesuai. Setelah "Simpan Pendataan", ID alat dibuat di sistem (barcode fisiknya baru ditempel nanti saat pengemasan) dan unit dapat notifikasi. Bisa juga filter daftar order berdasarkan <strong>Zona</strong> (Kotor/Bersih/Siap Distribusi) untuk lihat order mana yang alatnya ada di area tugas Anda.</p>
            </div>
        </div>

        <div id="cssd-dashboard" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📊 2. Dashboard & Pemindahan Massal</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Kartu "Rincian per Tahap" menunjukkan jumlah alat di tiap tahap <strong>lintas semua order</strong>. Untuk tahap Diterima/Proses Cuci/Proses Kering (belum ada barcode fisik, jadi belum bisa discan satu-satu), ada tombol "Mulai Cuci" / "Cuci Selesai" / "Pengeringan Selesai" — satu klik memindahkan SEMUA alat di tahap itu sekaligus, cocok untuk satu bak cuci/rak pengering yang isinya campuran beberapa order. Untuk memproses satu order tertentu terpisah (mis. CITO), tombol yang sama juga ada di halaman detail order itu.</p>
            </div>
        </div>

        <div id="cssd-scan" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📷 3. Stasiun Scan</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Dipakai begitu alat SUDAH ditempel barcode fisik (mulai tahap Pengemasan). Pilih tahap kerja di dropdown atas, lalu scan label QR (kamera HP via tombol "Buka Kamera", atau scanner fisik yang otomatis mengetik+Enter). Sistem menolak tegas kalau alat discan di tahap yang salah urutannya — itu bukan bug, itu supaya alat kotor tidak bisa "melompat" jadi steril tanpa lewat tahapnya.</p>
            </div>
        </div>

        <div id="cssd-qc" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">✅ 4. Checklist QC di halaman detail alat</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Buka detail satu alat (klik kode labelnya di mana pun), kalau statusnya persis di salah satu titik cek (Penerimaan/Dekontaminasi/Bersih-Packaging/Steril), muncul checklist QC di bagian atas. Kalau ada item "tidak sesuai", alat otomatis dikembalikan ke tahap sebelumnya yang relevan (beri catatan alasannya) dan unit asal dapat notifikasi.</p>
            </div>
        </div>

        <div id="cssd-replace" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🔄 5. Ganti Barcode</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Dipakai saat beberapa alat lepasan yang sudah bersih dirakit ulang jadi satu set baru — scan semua barcode lama, tentukan set/alat baru, dapat satu barcode baru. Barcode lama tidak hilang, ditandai "Barcode Diganti" dan tetap tertaut untuk riwayat.</p>
            </div>
        </div>

        <div id="cssd-distribution" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🚚 6. Distribusi</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Pilih unit tujuan, centang alat steril yang siap diserahkan, klik "Serahkan Alat" — ini membuat nomor <strong>PU-...</strong> dan unit langsung dapat notifikasi untuk konfirmasi. Kalau ada yang menanyakan nomor "PU-..." dari notifikasi tapi sudah tidak muncul di "Menunggu Konfirmasi", cari di kotak pencarian pada card <strong>Riwayat Distribusi</strong> di bawahnya.</p>
            </div>
        </div>
    @endif

    {{-- ================= UNIT / IBS ================= --}}
    @if ($role === \App\Enums\UserRole::Nakes)
        @php
            $topics = [
                ['id' => 'unit-inventory', 'icon' => '📋', 'title' => 'Pendataan Alat di Unit'],
                ['id' => 'unit-order', 'icon' => '📝', 'title' => 'Buat Order'],
                ['id' => 'unit-orders', 'icon' => '📄', 'title' => 'Order Saya'],
                ['id' => 'unit-pickup', 'icon' => '📥', 'title' => 'Penerimaan'],
                ['id' => 'unit-notif', 'icon' => '🔔', 'title' => 'Notifikasi'],
            ];
        @endphp

        <p class="mb-3 mt-6 text-sm font-semibold text-slate-900">Untuk Unit / IBS (Dokter, Perawat, Nakes) — pilih topik</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($topics as $t)
                <a href="#{{ $t['id'] }}" class="card flex items-center gap-3 p-4 transition hover:border-teal-300 hover:shadow">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-xl">{{ $t['icon'] }}</span>
                    <span class="text-sm font-medium text-slate-800">{{ $t['title'] }}</span>
                </a>
            @endforeach
        </div>

        <div id="unit-inventory" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📋 1. Pendataan Alat di Unit</div>
            <div class="space-y-1 px-5 py-4 text-sm text-slate-700">
                <p>Di sinilah Anda menandai alat yang sudah dipakai — <strong>bukan</strong> di form Buat Order. Daftar di halaman ini menunjukkan alat real-time yang ada di unit Anda (sudah diambil dari CSSD, belum dipakai).</p>
                <p>Alat lepasan (<strong>Per Barang</strong>): tinggal klik "Tandai Dipakai".</p>
                <p>Alat dalam <strong>Set</strong>: centang satu per satu isi set yang dipakai, lalu tekan tombol hijau <strong>"Konfirmasi Pemakaian"</strong> di bawahnya. Set baru berpindah status setelah tombol ini ditekan — jadi aman untuk mencentang beberapa alat dulu sebelum konfirmasi, set-nya tidak akan hilang dari daftar sebelum Anda menekan tombol.</p>
                <p>Alat yang sudah dikonfirmasi dipakai hilang dari daftar real-time ini dan baru muncul lagi di form Buat Order sebagai daftar alat kotor yang siap dikirim balik.</p>
            </div>
        </div>

        <div id="unit-order" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📝 2. Buat Order (kirim alat kotor ke CSSD)</div>
            <div class="space-y-1 px-5 py-4 text-sm text-slate-700">
                <p>Alat yang mau dikirim balik harus <strong>sudah</strong> ditandai "dipakai" lewat menu <strong>Pendataan Alat di Unit</strong> (topik di atas) terlebih dulu. Bagian "Alat Kotor (Sudah Dipakai)" di form ini cuma tampilan, tidak bisa diubah dari sini lagi.</p>
                <p>Isi form: nama pengantar, jam kirim, jumlah box, dan <strong>foto kondisi alat wajib diisi</strong> minimal 1 foto (foto yang salah/blur bisa dihapus sebelum dikirim). Kalau CITO, isi jam kebutuhan saja (tanpa tanggal) — sistem otomatis pakai hari ini atau besok tergantung jam berapa sekarang.</p>
                <p>Pilih alat yang ikut dikirim dari daftar dropdown per baris — hanya alat yang sudah berstatus "dipakai" yang muncul di pilihan, jadi tidak bisa salah pilih alat yang belum ditandai dipakai.</p>
            </div>
        </div>

        <div id="unit-orders" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📄 3. Order Saya</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Pantau status tiap order yang pernah dikirim. Rincian alat (per set/per barang) baru muncul setelah CSSD selesai mendata — sebelum itu hanya terlihat statusnya saja.</p>
            </div>
        </div>

        <div id="unit-pickup" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">📥 4. Penerimaan</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Saat notifikasi "alat steril siap diambil (nomor serah terima PU-...)" muncul, buka menu ini dan klik <strong>"Diterima"</strong> untuk konfirmasi. Setelah dikonfirmasi, alat masuk ke gudang internal unit — untuk menandai pemakaiannya nanti, buka menu <strong>Pendataan Alat di Unit</strong>.</p>
            </div>
        </div>

        <div id="unit-notif" class="card mt-5 overflow-hidden scroll-mt-4">
            <div class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">🔔 5. Notifikasi</div>
            <div class="px-5 py-4 text-sm text-slate-700">
                <p>Ikon lonceng di pojok bawah sidebar. Ada beberapa jenis: "Pendataan CSSD selesai" (rincian order Anda sudah bisa dilihat), "Alat steril siap/dikirim" (perlu konfirmasi penerimaan), dan "Alat hilang/rusak/gagal uji" (alat dari order Anda perlu perhatian).</p>
            </div>
        </div>
    @endif
</div>
