# SIM Alat CSSD — RS Kemenkes Surabaya

Sistem Informasi Manajemen alat medis di CSSD (*Central Sterile Supply Department*).
Menggantikan pencatatan kertas dengan pelacakan digital berbasis scan QR/barcode.

## Masalah yang diselesaikan

1. **Pencatatan manual berisiko** — alat hilang tanpa jejak digital.
2. **Unit lain harus telepon/WA CSSD** untuk tanya status alat.
3. **Tidak ada bukti posisi terakhir alat** saat audit kehilangan.

Setiap perpindahan status dicatat lengkap dengan **pelaku + waktu** (jejak audit
*append-only*), sehingga posisi terakhir alat selalu bisa dibuktikan.

## Teknologi

| Komponen | Versi | Peran |
|---|---|---|
| PHP | 8.4 | — |
| Laravel | 13 | Framework |
| Livewire | 4 (class-based) | UI server-rendered, polling live tracking |
| Tailwind CSS | 4 | Tampilan |
| MariaDB 10.4 / MySQL 8 | — | Database |
| `endroid/qr-code` | 6 | Membuat label QR (SVG) |
| `qr-scanner` (npm) | 1.4 | Scan QR lewat kamera browser |
| `barryvdh/laravel-dompdf` | 3 | Cetak laporan sterilisasi (PDF) |

## Kebutuhan Sistem

- **PHP 8.3+** dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `gd`, `intl`
  (PHP bawaan XAMPP 8.0 **tidak cukup** — Laravel 13 minimal butuh PHP 8.3)
- **Composer 2**
- **Node.js 20+** & npm
- **MariaDB / MySQL**

## Instalasi

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
# lalu sesuaikan DB_DATABASE / DB_USERNAME / DB_PASSWORD di .env

# 3. Database
php artisan migrate --seed

# 4. Build aset frontend
npm run build
```

## Menjalankan

```bash
php artisan serve
```

Buka <http://127.0.0.1:8000>.

> **Windows + XAMPP:** pastikan MariaDB sudah jalan (XAMPP Control Panel → Start MySQL),
> atau pakai `start-dev.bat` di root proyek yang otomatis menyalakan MariaDB lalu server Laravel.

## Akun Demo (hasil seeder)

Kata sandi semua akun: **`password`**

| Peran | Email | Unit |
|---|---|---|
| Admin | `admin@rskemenkes.test` | (lintas unit) |
| Petugas CSSD | `cssd1@rskemenkes.test` | CSSD |
| Petugas CSSD | `cssd2@rskemenkes.test` | CSSD |
| Dokter/Nakes | `ibs@rskemenkes.test` | Instalasi Bedah Sentral |
| Dokter/Nakes | `igd@rskemenkes.test` | Instalasi Gawat Darurat |
| Dokter/Nakes | `icu@rskemenkes.test` | Ruang ICU |

> ⚠️ **Kata sandi di atas hanya untuk pengembangan/demo.**
> Sebelum dipakai di rumah sakit, semua akun **wajib** ganti kata sandi dan
> `APP_DEBUG` harus di-set `false`.

## Peran & Hak Akses

| Peran | Hak |
|---|---|
| **Admin** | Manajemen pengguna & master data, koreksi *human error*, akses area CSSD |
| **Petugas CSSD** | Pendataan barang (Per Set / Per Barang), eksekusi alur Zona Kotor → Zona Bersih |
| **Dokter / Perawat / Nakes** | Buat order pengiriman, konfirmasi penerimaan, pantau status alat unitnya |

Prinsip: **master data & akun tidak pernah dihapus permanen**, hanya dinonaktifkan —
agar riwayat order dan jejak audit lama tetap utuh dan bisa ditelusuri.

## Pengujian

```bash
php artisan test
```

Test berjalan di atas MariaDB (database `sim_cssd_test`) — bukan SQLite — supaya
perilaku *foreign key* dan tipe kolom sama persis dengan produksi.

Buat database test sekali di awal:

```sql
CREATE DATABASE sim_cssd_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Status Pembangunan

**Alur inti: SELESAI ✅** — satu siklus penuh sudah berjalan end-to-end.

- [x] Master data & autentikasi per peran (Admin / Petugas CSSD / Nakes)
- [x] Gudang steril, pendaftaran aset, kelola batch per unit
- [x] Pemesanan alat ala *checkout* + pesan ulang batch
- [x] Penyiapan CSSD + serah terima berfoto
- [x] Pengembalian alat kotor dengan **konfirmasi dua sisi** + foto
- [x] Scan barcode baru: foto set + **checklist isi set** (✓/✗)
- [x] Sterilisasi & penyelesaian (scan satuan atau satu batch sekaligus)
- [x] Katalog stok untuk unit, scan pemakaian, live tracking
- [x] Telusur audit lintas barcode, laporan set tidak lengkap, koreksi Admin
- [x] **Laporan PDF proses sterilisasi** (pengganti formulir kertas)

**Belum dikerjakan:** rekap/ekspor lanjutan, *push* real-time (Laravel Reverb),
integrasi ke SIM RS.

## Alur Siklus Lengkap

```
        ┌──────────────── Gudang Steril (alat siap dipesan) ────────────────┐
        │                                                                   │
        ▼                                                                   │
  Unit checkout  →  CSSD alokasi & siapkan  →  serah terima (+ FOTO)        │
        │                                                                   │
        ▼                                                                   │
  Unit konfirmasi terima  →  perawat scan "dipakai"                         │
        │                                                                   │
        ▼                                                                   │
  Unit kirim balik (+ foto, tentukan pesan ulang / lepas batch)             │
        │                                                                   │
        ▼                                                                   │
  CSSD konfirmasi terima  ←── KONFIRMASI DUA SISI                           │
        │                                                                   │
        ▼                                                                   │
  Pencucian & dekontaminasi  (barcode LAMA masih berlaku)                   │
        │                                                                   │
        ▼                                                                   │
  Scan Barcode Baru  →  set: FOTO + CHECKLIST ISI (✓ ada / ✗ hilang)        │
        │                                                                   │
        ▼                                                                   │
  Sterilisasi  →  Selesai  ──────────────────────────────────────────────────┘
```

Setiap panah menghasilkan **satu baris jejak audit permanen** berisi status asal,
status tujuan, pelaku, waktu, dan metode input (kamera / scanner / manual).

### Dua jenis barcode

| Jenis | Cakupan | Perlakuan saat barcode diganti |
|---|---|---|
| **Set** | Satu barcode mewakili seluruh isi set | Wajib **foto rakitan** + **checklist isi**; isi yang disilang menandai set tidak lengkap |
| **Alat satuan** | Satu barcode untuk satu alat | Langsung terupdate saat barcode baru discan |

### Batch = "langganan alat" unit

Batch menetap lintas siklus. Saat mengembalikan alat kotor, unit menentukan:
**pesan ulang** (alat kembali jadi milik batch unit) atau **lepas** (alat masuk
stok bebas dan bisa dipesan unit lain).

## Prinsip Perancangan Penting

| Prinsip | Alasan |
|---|---|
| **Jejak audit bersifat *append-only*** | Baris jejak tidak bisa di-`update` maupun di-`delete` (dijaga di level model). Inilah bukti posisi alat saat audit kehilangan. |
| **Transisi tidak sah ditolak tegas** | Alat kotor tidak bisa melompat ke gudang steril. Kegagalan dimunculkan jelas ke petugas — konteks keselamatan pasien tidak boleh gagal diam-diam. |
| **Scan bersifat idempotent** | Scanner HID yang terpicu dua kali tidak membuat jejak kembar. |
| **Baris dikunci saat transisi** | `lockForUpdate` di dalam transaksi mencegah dua petugas saling menimpa saat men-scan alat yang sama. |
| **Master data tidak dihapus permanen** | Hanya dinonaktifkan, agar riwayat order lama tetap terbaca. |
| **Barcode lama tetap berlaku selama pencucian** | Label fisiknya dibuang saat kemasan dibuka, tapi karena sudah discan masuk, sistem tetap mengenalinya — pelacakan tidak pernah terputus. Barcode baru menggantikan hanya saat discan. |
| **Riwayat barcode tersimpan** | Kode lama tetap bisa dicari saat audit walau alat sudah berganti label berkali-kali. |
| **Set tidak lengkap tidak menahan proses** | Sistem menandai & melaporkan, tapi keputusan menahan diserahkan pada manusia — supaya kerja CSSD tidak macet. |
| **Konfirmasi dua sisi pada pengembalian** | Selisih serah terima ketahuan saat itu juga, bukan baru saat audit. |
| **Koreksi Admin wajib beralasan** | Tercatat dengan penanda *override* — bukan perubahan diam-diam. |

## Data Demo (opsional)

Untuk melihat sistem dengan alur yang sudah berjalan di berbagai tahap:

```bash
php artisan db:seed --class=DemoFlowSeeder
```

Mendaftarkan ~64 aset dan menjalankan 6 skenario yang berhenti di tahap berbeda
(pesanan menunggu disiapkan, siap diambil, di unit, kiriman menunggu konfirmasi,
sedang dicuci, sedang disterilkan) — sehingga setiap layar langsung ada isinya.
**Jangan dijalankan di produksi.**

## Catatan Deployment

- **HTTPS wajib** meski hanya diakses via intranet RS — scan QR lewat kamera browser
  (`getUserMedia`) hanya jalan di *secure context*.
- **Barcode scanner fisik**: gunakan tipe **2D imager**, bukan 1D laser, karena
  sistem memakai QR untuk semua jenis batch dan QR lebih tahan label yang terpapar
  panas/lembap di dekat autoclave.
