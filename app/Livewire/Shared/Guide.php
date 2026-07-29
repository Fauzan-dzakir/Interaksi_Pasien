<?php

namespace App\Livewire\Shared;

use App\Enums\UserRole;
use Livewire\Component;

/**
 * Panduan singkat penggunaan sistem, disesuaikan dengan peran yang sedang masuk.
 *
 * Ditampilkan otomatis di dashboard saat pertama kali dibuka, dan bisa dibuka
 * ulang kapan saja lewat menu, supaya petugas tidak perlu menghafal alur.
 */
class Guide extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.shared.guide', [
            'role' => $user->role,
            'steps' => $this->stepsFor($user->role),
            'tips' => $this->tipsFor($user->role),
        ]);
    }

    /** @return array<int, array{title: string, body: string, route: ?string, action: ?string}> */
    private function stepsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Nakes => [
                [
                    'title' => 'Buat pesanan cuci',
                    'body' => 'Cukup foto alat kotor yang akan dikirim, bisa langsung dijepret dari kamera. Anda tidak perlu mendata barang satu per satu. Beri catatan bila perlu, dan centang Pasien CITO bila alat dibutuhkan segera.',
                    'route' => 'unit.orders.create',
                    'action' => 'Pesan Cuci',
                ],
                [
                    'title' => 'Antar alatnya ke CSSD',
                    'body' => 'Setelah pesanan dibuat, antar alat kotornya. Petugas CSSD akan men-scan barcode tiap alat yang datang, dan hasilnya langsung muncul di pesanan Anda.',
                    'route' => 'unit.orders',
                    'action' => 'Pesanan Saya',
                ],
                [
                    'title' => 'Pantau progresnya',
                    'body' => 'Lihat sampai mana alat Anda diproses, mulai dari pencucian sampai selesai steril. Tidak perlu lagi menelepon CSSD untuk bertanya.',
                    'route' => 'unit.progress',
                    'action' => 'Progress Pencucian',
                ],
                [
                    'title' => 'Konfirmasi saat alat kembali',
                    'body' => 'Begitu alat selesai, Anda akan mendapat notifikasi. Tekan Konfirmasi Diterima agar tanggung jawab alat resmi berpindah kembali ke unit Anda.',
                    'route' => 'unit.orders',
                    'action' => 'Lihat Pesanan',
                ],
                [
                    'title' => 'Tandai alat yang dipakai',
                    'body' => 'Scan barcode alat yang benar-benar digunakan agar posisinya tercatat. Alat yang tidak jadi dipakai tidak perlu discan.',
                    'route' => 'unit.usage',
                    'action' => 'Scan Pemakaian',
                ],
            ],

            UserRole::CssdStaff => [
                [
                    'title' => 'Terima alat kotor dari unit',
                    'body' => 'Buka Pesanan, dahulukan yang bertanda CITO. Scan barcode tiap alat yang datang. Di sinilah pendataan terjadi, karena unit hanya mengirim foto.',
                    'route' => 'cssd.orders',
                    'action' => 'Buka Pesanan',
                ],
                [
                    'title' => 'Konfirmasi kiriman langsung',
                    'body' => 'Kiriman alat kotor di luar pesanan muncul di Kiriman Kotor. Konfirmasi penerimaannya agar alat masuk tahap pencucian.',
                    'route' => 'cssd.returns',
                    'action' => 'Buka Kiriman',
                ],
                [
                    'title' => 'Pasang barcode baru setelah dekontaminasi',
                    'body' => 'Scan barcode lama, lalu masukkan barcode baru. Untuk set, wajib unggah foto dan periksa isinya satu per satu dengan tanda centang atau silang.',
                    'route' => 'cssd.new-barcode',
                    'action' => 'Scan Barcode Baru',
                ],
                [
                    'title' => 'Sterilkan dan selesaikan',
                    'body' => 'Di Stasiun Scan, pindahkan alat ke tahap sterilisasi. Setelah selesai, Anda bisa menyelesaikan satu batch sekaligus tanpa perlu memindai satu per satu.',
                    'route' => 'cssd.scan',
                    'action' => 'Stasiun Scan',
                ],
                [
                    'title' => 'Kembalikan ke unit',
                    'body' => 'Begitu seluruh alat pada satu pesanan selesai steril, tombol Kembalikan ke Unit muncul. Sertakan foto bukti serah terima, unit akan langsung diberi notifikasi.',
                    'route' => 'cssd.orders',
                    'action' => 'Buka Pesanan',
                ],
                [
                    'title' => 'Cetak laporan proses',
                    'body' => 'Buat laporan sterilisasi lalu cetak PDF. Kolom tahapan, jam, dan nama petugas terisi otomatis dari catatan pemindaian Anda.',
                    'route' => 'cssd.reports',
                    'action' => 'Buka Laporan',
                ],
            ],

            UserRole::Admin => [
                [
                    'title' => 'Siapkan master data',
                    'body' => 'Isi daftar unit, katalog alat, dan set alat. Unggah foto contoh tiap alat agar petugas mudah mengenalinya secara visual.',
                    'route' => 'admin.items',
                    'action' => 'Katalog Alat',
                ],
                [
                    'title' => 'Buat akun pengguna',
                    'body' => 'Semua akun dibuat di sini, tidak ada pendaftaran mandiri. Tentukan peran dan unit asal tiap pengguna.',
                    'route' => 'admin.users',
                    'action' => 'Kelola Pengguna',
                ],
                [
                    'title' => 'Telusuri alat saat ada masalah',
                    'body' => 'Cari alat berdasarkan barcode lama maupun baru untuk melihat posisi terakhirnya, lengkap dengan jam dan nama petugas.',
                    'route' => 'admin.audit',
                    'action' => 'Telusur Alat',
                ],
                [
                    'title' => 'Pantau set yang tidak lengkap',
                    'body' => 'Set yang isinya hilang saat pemeriksaan dikumpulkan di sini, lengkap dengan alat mana yang paling sering hilang sebagai bahan evaluasi SOP.',
                    'route' => 'admin.incomplete-sets',
                    'action' => 'Lihat Laporan',
                ],
            ],
        };
    }

    /** @return array<int, string> */
    private function tipsFor(UserRole $role): array
    {
        $shared = [
            'Semua perpindahan alat tercatat permanen beserta nama dan jam. Catatan ini tidak bisa diubah maupun dihapus.',
        ];

        return match ($role) {
            UserRole::Nakes => array_merge([
                'Anda tidak perlu mendata barang. Cukup foto alat kotornya, CSSD yang mendata lewat scan barcode.',
                'Waktu dibutuhkan hanya diisi untuk pesanan CITO.',
            ], $shared),

            UserRole::CssdStaff => array_merge([
                'Sistem menolak pemindaian di tahap yang salah. Ini disengaja demi keselamatan pasien.',
                'Barcode lama tetap berlaku selama pencucian, walaupun label fisiknya sudah dibuang.',
                'Set yang isinya tidak lengkap tetap boleh diproses. Sistem hanya menandai dan melaporkannya.',
            ], $shared),

            UserRole::Admin => array_merge([
                'Master data tidak pernah dihapus permanen, hanya dinonaktifkan, agar riwayat lama tetap terbaca.',
                'Koreksi Admin wajib disertai alasan dan selalu ditandai khusus pada jejak audit.',
            ], $shared),
        };
    }
}
