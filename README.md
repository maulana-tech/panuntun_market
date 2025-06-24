# 🏪 Panuntun Market - Sistem Pengelolaan Kas

> Sistem pengelolaan kas berbasis web untuk Minimarket Panuntun yang memudahkan pencatatan transaksi, manajemen inventory, dan pelaporan keuangan secara real-time.

## 📋 Daftar Isi
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#️-teknologi)
- [Quick Start](#-quick-start)
- [Instalasi Lengkap](#-instalasi-lengkap)
- [Penggunaan](#-penggunaan)
- [Struktur Database](#️-struktur-database)
- [Troubleshooting](#-troubleshooting)

## ✨ Fitur Utama

### 👨‍💼 Admin Dashboard
- ✅ **Manajemen Data** - Kelola pengguna, supplier, dan produk
- 💰 **Transaksi** - Catat pembelian dan penjualan
- 📊 **Kas Otomatis** - Auto-generate kas masuk/keluar dari transaksi
- 📋 **Laporan** - Lihat semua data dan riwayat transaksi

### 👑 Owner Dashboard
- 👥 **Monitoring User** - Pantau pengguna yang terdaftar
- 📈 **Laporan Lengkap** - Akses semua laporan bisnis
  - Jurnal Umum
  - Laporan Kas Masuk/Keluar per Periode
  - Dan masih banyak lagi

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|----------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ |
| **Server** | Apache (XAMPP) |
| **Frontend** | HTML, CSS, JavaScript |
| **Platform** | Cross-platform (Windows, macOS, Linux) |


3. Start XAMPP services
- Buka XAMPP Control Panel dan start Apache + MySQL

4. Setup database
- Buka http://localhost/phpmyadmin
- Buat database: db_minimarket_panuntun
- Import file: db.sql

5. Akses aplikasi
- http://localhost/panuntun_market/

## 📦 Instalasi Lengkap

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) atau WAMP
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web browser modern

### Langkah Instalasi

#### 1️⃣ Download & Setup
```bash
# Clone repository
git clone https://github.com/maulana-tech/panuntun_market.git

```

1. Pindahkan ke direktori server
- Windows: C:\xampp\htdocs\panuntun_market\


#### 2️⃣ Database Setup
1. **Start XAMPP** - Jalankan Apache dan MySQL
2. **Buka phpMyAdmin** - Kunjungi `http://localhost/phpmyadmin`
3. **Buat Database**
   - Nama: `db_minimarket_panuntun`
4. **Import Database**
   - Pilih database yang baru dibuat
   - Tab "Import" → Choose file → Pilih `db.sql`
   - Klik "Go"

#### 3️⃣ Konfigurasi (Opsional)
Jika perlu mengubah setting database, edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_minimarket_panuntun');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 🎯 Penggunaan

### Akses Aplikasi
- **URL Utama**: `http://localhost/panuntun_market/`
- **Login**: `http://localhost/panuntun_market/auth/login.php`

### Default Login
| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `admin123` |
| **Owner** | `owner` | `owner123` |

> ⚠️ **Penting**: Ubah password default setelah login pertama untuk keamanan!

### Struktur Folder
```
panuntun_market/
├── index.php              # Halaman utama
├── auth/                   # Sistem autentikasi
│   ├── login.php
│   └── logout.php
├── components/             # Komponen UI
│   ├── header.php
│   ├── footer.php
│   └── navigation.php
├── config/                 # Konfigurasi database
├── pages/                  # Halaman aplikasi
│   ├── dashboard.php
│   ├── users.php
│   ├── products.php
│   ├── suppliers.php
│   ├── sales.php
│   ├── purchases.php
│   └── reports/           # Laporan
├── assets/                # CSS, JS, Images
├── includes/              # Functions & utilities
└── db.sql                # Database schema
```

## 🗃️ Struktur Database

Sistem menggunakan 8 tabel utama:

| Tabel | Fungsi |
|-------|--------|
| **users** | Data pengguna sistem |
| **products** | Informasi produk/barang |
| **suppliers** | Data supplier |
| **purchases** | Transaksi pembelian |
| **sales** | Transaksi penjualan |
| **cash_inflow** | Kas masuk |
| **cash_outflow** | Kas keluar |
| **cash_ledger** | Buku besar kas |

## 🔧 Troubleshooting

### ❌ Database Connection Error
```
Solusi:
✅ Pastikan MySQL service running di XAMPP
✅ Cek database 'db_minimarket_panuntun' sudah dibuat
✅ Verifikasi config/config.php
```

### ❌ Page Not Found (404)
```
Solusi:
✅ Pastikan Apache service running di XAMPP
✅ Cek folder proyek di htdocs/
✅ Verifikasi URL yang diakses
```

### ❌ PHP Errors
```
Solusi:
✅ Pastikan PHP version minimal 7.4
✅ Cek PHP error log di XAMPP control panel
✅ Pastikan semua ekstensi PHP terinstall
```

### 🔄 Cara Stop Server
- **XAMPP**: Klik "Stop" untuk Apache dan MySQL
- **Command Line**: `Ctrl+C` jika menggunakan built-in server

