# Sistem Pengelolaan Kas Berbasis Web - Minimarket Panuntun

Ini adalah sistem pengelolaan kas berbasis web yang dirancang untuk Minimarket Panuntun. [cite_start]Sistem ini bertujuan untuk mengatur, mencatat, dan melaporkan semua transaksi yang berhubungan dengan pemasukan dan pengeluaran kas. [cite_start]Sistem ini dibangun untuk berjalan di server lokal (localhost).

## Fitur Utama

Sistem ini memiliki dua hak akses utama, yaitu **Admin** dan **Owner**, dengan fungsionalitas yang berbeda.

### Fitur Admin
* [cite_start]Mengelola (input) data pengguna, data supplier, dan data barang.
* [cite_start]Mencatat transaksi pembelian dan penjualan.
* [cite_start]Secara otomatis membuat catatan kas masuk dari setiap transaksi penjualan.
* [cite_start]Secara otomatis membuat catatan kas keluar dari setiap transaksi pembelian.
* [cite_start]Melihat daftar data seperti daftar pengguna, supplier, barang, pembelian, dan penjualan.

### Fitur Owner
* [cite_start]Melihat data pengguna yang terdaftar.
* [cite_start]Mengakses dan mencetak laporan-laporan penting, seperti:
    * [cite_start]Daftar Pengguna, Supplier, dan Barang.
    * [cite_start]Jurnal Umum.
    * [cite_start]Laporan Kas Masuk per Periode.
    * [cite_start]Laporan Kas Keluar per Periode.
    * [cite_start]Laporan Kas.

## Teknologi yang Digunakan

[cite_start]Proyek ini dikembangkan menggunakan tumpukan teknologi berikut:
* **Bahasa Pemrograman**: PHP
* **Database**: MySQL
* **Sistem Operasi**: Windows 10
* **Web Server**: Localhost (seperti XAMPP, WAMP)
* **Browser**: Chrome

## Desain Database

[cite_start]Sistem ini menggunakan basis data relasional dengan 8 tabel utama untuk menyimpan semua data secara terstruktur.
1.  [cite_start]**Tabel Pengguna**: Menyimpan data pengguna yang dapat mengakses sistem.
2.  [cite_start]**Tabel Barang**: Menyimpan informasi barang yang dijual atau dibeli.
3.  [cite_start]**Tabel Supplier**: Menyimpan data supplier yang memasok barang.
4.  [cite_start]**Tabel Pembelian**: Mencatat semua detail transaksi pembelian barang.
5.  [cite_start]**Tabel Penjualan**: Mencatat semua detail transaksi penjualan barang.
6.  [cite_start]**Tabel Kas Masuk**: Menyimpan data dari setiap transaksi kas yang masuk.
7.  [cite_start]**Tabel Kas Keluar**: Menyimpan data dari setiap transaksi kas yang keluar.
8.  [cite_start]**Tabel Kas**: Berfungsi sebagai buku besar yang mencatat semua transaksi kas masuk, keluar, dan saldo akhir.

## Panduan Instalasi dan Menjalankan Server

Untuk menjalankan proyek ini di lingkungan lokal Anda, ikuti langkah-langkah berikut:

### Prasyarat
* XAMPP atau WAMP terinstall
* PHP 7.4 atau lebih tinggi
* MySQL 5.7 atau lebih tinggi
* Web browser (Chrome, Firefox, dll.)

### Langkah-langkah Instalasi

1.  **Clone Repositori**
    ```bash
    git clone https://github.com/maulana-tech/panuntun_market.git
    ```

2.  **Pindahkan ke Direktori Server**
    * Pindahkan folder proyek `panuntun_market` ke dalam direktori `htdocs` (jika menggunakan XAMPP) atau `www` (jika menggunakan WAMP).
    * Pastikan path lengkapnya adalah: `/Applications/XAMPP/xamppfiles/htdocs/panuntun_market/` (untuk macOS) atau `C:\xampp\htdocs\panuntun_market\` (untuk Windows)

3.  **Setup Database**
    * Pastikan XAMPP sudah berjalan (Apache dan MySQL)
    * Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
    * Buat database baru dengan nama: `db_minimarket_panuntun`
    * Impor file `db.sql` yang ada di root proyek ke dalam database yang baru dibuat:
      - Klik database `db_minimarket_panuntun`
      - Pilih tab "Import"
      - Choose file: pilih `db.sql` dari folder proyek
      - Klik "Go" untuk mengimpor

4.  **Konfigurasi Database** (Opsional)
    * File konfigurasi database sudah diatur di `config/config.php`
    * Jika perlu mengubah pengaturan database, edit file tersebut:
    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'db_minimarket_panuntun');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    ```

### Cara Menjalankan Server

#### Opsi 1: Menggunakan XAMPP (Rekomendasi)
1.  **Start XAMPP Services**
    * Buka XAMPP Control Panel
    * Klik "Start" untuk Apache dan MySQL
    * Pastikan kedua service berstatus "Running" (hijau)

2.  **Akses Aplikasi**
    * Buka browser dan kunjungi: `http://localhost/panuntun_market/pages/`
    * Atau langsung ke halaman login: `http://localhost/panuntun_market/pages/login.php`

#### Opsi 2: Menggunakan PHP Built-in Server
1.  **Buka Terminal/Command Prompt**
    * Navigasi ke folder proyek:
    ```bash
    cd /Applications/XAMPP/xamppfiles/htdocs/panuntun_market
    ```

2.  **Jalankan MySQL terlebih dahulu**
    * Pastikan MySQL XAMPP sudah berjalan
    * Atau start MySQL melalui XAMPP Control Panel

3.  **Start PHP Server**
    ```bash
    php -S localhost:8000 -t pages/
    ```

4.  **Akses Aplikasi**
    * Buka browser dan kunjungi: `http://localhost:8000`
    * Atau langsung ke: `http://localhost:8000/login.php`

### Mengakses Aplikasi

#### Default Login Credentials
**Admin:**
- Username: `admin`
- Password: `admin123`

**Owner:**
- Username: `owner`
- Password: `owner123`

*(Catatan: Ganti password default setelah login pertama untuk keamanan)*

### Troubleshooting

**Problem: Database connection error**
- Pastikan MySQL service di XAMPP sudah running
- Cek apakah database `db_minimarket_panuntun` sudah dibuat
- Verifikasi pengaturan di `config/config.php`

**Problem: Page not found (404)**
- Pastikan Apache service di XAMPP sudah running
- Cek apakah folder proyek sudah berada di `htdocs`
- Verifikasi URL yang diakses

**Problem: PHP errors**
- Pastikan PHP version minimal 7.4
- Cek PHP error log di XAMPP control panel

### Menghentikan Server

**Jika menggunakan XAMPP:**
- Klik "Stop" untuk Apache dan MySQL di XAMPP Control Panel

**Jika menggunakan PHP built-in server:**
- Tekan `Ctrl+C` di terminal untuk menghentikan server

## Struktur Folder Proyek