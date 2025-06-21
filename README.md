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

## Panduan Instalasi

Untuk menjalankan proyek ini di lingkungan lokal Anda, ikuti langkah-langkah berikut:

1.  **Clone Repositori**
    ```bash
    git clone https://[URL_repositori_Anda].git
    ```
2.  **Pindahkan ke Direktori Server**
    * Pindahkan folder proyek `panuntun_minimarket_kas` ke dalam direktori `htdocs` (jika menggunakan XAMPP) atau `www` (jika menggunakan WAMP).

3.  **Setup Database**
    * Buka phpMyAdmin (`http://localhost/phpmyadmin`).
    * Buat database baru dengan nama (misalnya) `db_kas_panuntun`.
    * Impor file `.sql` yang berisi struktur tabel ke dalam database yang baru Anda buat.

4.  **Konfigurasi Koneksi**
    * Buka file `src/config.php`.
    * Sesuaikan detail koneksi database (`$host`, `$user`, `$pass`, `$dbname`) dengan konfigurasi server lokal Anda.

5.  **Jalankan Aplikasi**
    * Buka browser Anda dan akses proyek melalui URL:
    ```
    http://localhost/panuntun_minimarket_kas/public/
    ```

## Struktur Folder Proyek