import QrScanner from 'qr-scanner';

// Dipakai oleh komponen Alpine "scanStation" di halaman stasiun scan.
// Diekspos lewat window karena skrip komponen Livewire (@script) berjalan
// di luar bundel modul Vite sehingga tidak bisa meng-import langsung.
window.QrScanner = QrScanner;
