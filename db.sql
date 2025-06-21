

CREATE TABLE Pengguna (
    Id_Pengguna INT(11) PRIMARY KEY,
    Nama VARCHAR(30),
    Jabatan VARCHAR(30),
    Email VARCHAR(30),
    Pass VARCHAR(255)
);

CREATE TABLE Barang (
    Kode_Barang INT PRIMARY KEY,
    Nama_Barang CHAR(30),
    Stok INT
);

CREATE TABLE Supplier (
    Id_Supplier INT PRIMARY KEY,
    Nama_Supplier CHAR(30),
    No_Tlp INT,
    Alamat CHAR(30)
);

CREATE TABLE Pembelian (
    Id_Pembelian INT PRIMARY KEY,
    Id_Supplier INT,
    Kode_Barang INT,
    Nama_Barang VARCHAR(30),
    Tgl_beli DATE,
    Harga INT,
    Qty INT,
    Total_Pembelian INT,
    FOREIGN KEY (Id_Supplier) REFERENCES Supplier(Id_Supplier),
    FOREIGN KEY (Kode_Barang) REFERENCES Barang(Kode_Barang)
);

CREATE TABLE Penjualan (
    Id_Penjualan INT PRIMARY KEY,
    Kode_Barang INT,
    Nama_Barang VARCHAR(30),
    Tgl_Jual DATE,
    Harga INT,
    Qty INT,
    Total_Penjualan INT,
    FOREIGN KEY (Kode_Barang) REFERENCES Barang(Kode_Barang)
);

CREATE TABLE Kas_Masuk (
    Id_Kas_Masuk INT PRIMARY KEY,
    Id_Penjualan INT,
    Keterangan VARCHAR(30),
    Tgl_Transaksi DATE,
    Jumlah INT,
    FOREIGN KEY (Id_Penjualan) REFERENCES Penjualan(Id_Penjualan)
);

CREATE TABLE Kas_Keluar (
    Id_Kas_Keluar INT PRIMARY KEY,
    Id_Pembelian INT,
    Keterangan VARCHAR(30),
    Tgl_Transaksi DATE,
    Jumlah INT,
    FOREIGN KEY (Id_Pembelian) REFERENCES Pembelian(Id_Pembelian)
);

CREATE TABLE Kas (
    Id_Kas INT PRIMARY KEY,
    Id_Kas_Masuk INT NULL,
    Id_Kas_Keluar INT NULL,
    Tgl_Transaksi DATE,
    Ket_Transaksi VARCHAR(30),
    Saldo INT,
    FOREIGN KEY (Id_Kas_Masuk) REFERENCES Kas_Masuk(Id_Kas_Masuk),
    FOREIGN KEY (Id_Kas_Keluar) REFERENCES Kas_Keluar(Id_Kas_Keluar)
);
