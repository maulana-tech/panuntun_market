-- Cash Flow Management System Database Schema
-- Versi ini sudah disesuaikan untuk MySQL/MariaDB

-- Users table (pengguna)
CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(30) NOT NULL,
    jabatan VARCHAR(30) NOT NULL CHECK (jabatan IN ('Admin', 'Owner')),
    email VARCHAR(30) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table (barang)
CREATE TABLE IF NOT EXISTS barang (
    kode_barang INT PRIMARY KEY AUTO_INCREMENT,
    nama_barang VARCHAR(30) NOT NULL,
    stok INT NOT NULL DEFAULT 0 CHECK (stok >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Suppliers table (supplier)
CREATE TABLE IF NOT EXISTS supplier (
    id_supplier INT PRIMARY KEY AUTO_INCREMENT,
    nama_supplier VARCHAR(30) NOT NULL,
    no_tlp VARCHAR(15),
    alamat VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Purchase transactions table (pembelian)
CREATE TABLE IF NOT EXISTS pembelian (
    id_pembelian INT PRIMARY KEY AUTO_INCREMENT,
    id_supplier INT NOT NULL,
    kode_barang INT NOT NULL,
    nama_barang VARCHAR(30) NOT NULL,
    tgl_beli DATE NOT NULL,
    harga INT NOT NULL CHECK (harga > 0),
    qty INT NOT NULL CHECK (qty > 0),
    total_pembelian INT NOT NULL CHECK (total_pembelian > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier) ON DELETE RESTRICT,
    FOREIGN KEY (kode_barang) REFERENCES barang(kode_barang) ON DELETE RESTRICT
);

-- Sales transactions table (penjualan)
CREATE TABLE IF NOT EXISTS penjualan (
    id_penjualan INT PRIMARY KEY AUTO_INCREMENT,
    kode_barang INT NOT NULL,
    nama_barang VARCHAR(30) NOT NULL,
    tgl_jual DATE NOT NULL,
    harga INT NOT NULL CHECK (harga > 0),
    qty INT NOT NULL CHECK (qty > 0),
    total_penjualan INT NOT NULL CHECK (total_penjualan > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_barang) REFERENCES barang(kode_barang) ON DELETE RESTRICT
);

-- Cash inflow table (kas_masuk)
CREATE TABLE IF NOT EXISTS kas_masuk (
    id_kas_masuk INT PRIMARY KEY AUTO_INCREMENT,
    id_penjualan INT NOT NULL,
    keterangan VARCHAR(50),
    tgl_transaksi DATE NOT NULL,
    jumlah INT NOT NULL CHECK (jumlah > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE RESTRICT
);

-- Cash outflow table (kas_keluar)
CREATE TABLE IF NOT EXISTS kas_keluar (
    id_kas_keluar INT PRIMARY KEY AUTO_INCREMENT,
    id_pembelian INT NOT NULL,
    keterangan VARCHAR(50),
    tgl_transaksi DATE NOT NULL,
    jumlah INT NOT NULL CHECK (jumlah > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id_pembelian) ON DELETE RESTRICT
);

-- Cash summary table (kas)
CREATE TABLE IF NOT EXISTS kas (
    id_kas INT PRIMARY KEY AUTO_INCREMENT,
    id_kas_masuk INT,
    id_kas_keluar INT,
    tgl_transaksi DATE NOT NULL,
    ket_transaksi VARCHAR(50) NOT NULL,
    saldo INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kas_masuk) REFERENCES kas_masuk(id_kas_masuk) ON DELETE RESTRICT,
    FOREIGN KEY (id_kas_keluar) REFERENCES kas_keluar(id_kas_keluar) ON DELETE RESTRICT,
    CHECK ((id_kas_masuk IS NOT NULL AND id_kas_keluar IS NULL) OR 
           (id_kas_masuk IS NULL AND id_kas_keluar IS NOT NULL))
);

-- Create indexes for better performance
CREATE INDEX idx_pengguna_email ON pengguna(email);
CREATE INDEX idx_pembelian_supplier ON pembelian(id_supplier);
CREATE INDEX idx_pembelian_barang ON pembelian(kode_barang);
CREATE INDEX idx_pembelian_tanggal ON pembelian(tgl_beli);
CREATE INDEX idx_penjualan_barang ON penjualan(kode_barang);
CREATE INDEX idx_penjualan_tanggal ON penjualan(tgl_jual);
CREATE INDEX idx_kas_masuk_tanggal ON kas_masuk(tgl_transaksi);
CREATE INDEX idx_kas_keluar_tanggal ON kas_keluar(tgl_transaksi);
CREATE INDEX idx_kas_tanggal ON kas(tgl_transaksi);

-- Insert default admin user (password: admin123)
-- Password hash for 'admin123' using bcrypt
INSERT IGNORE INTO pengguna (nama, jabatan, email, password) VALUES 
('Administrator', 'Admin', 'admin@panuntun.com', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBdXfs2Stk5v9W');

-- Insert sample data for testing
INSERT IGNORE INTO supplier (nama_supplier, no_tlp, alamat) VALUES 
('PT Sumber Rejeki', '081234567890', 'Jl. Raya No. 123, Jakarta'),
('CV Maju Bersama', '081234567891', 'Jl. Sudirman No. 456, Bandung'),
('UD Berkah Jaya', '081234567892', 'Jl. Gatot Subroto No. 789, Surabaya');

INSERT IGNORE INTO barang (nama_barang, stok) VALUES 
('Beras Premium 5kg', 50),
('Minyak Goreng 1L', 30),
('Gula Pasir 1kg', 25),
('Teh Celup 25pcs', 40),
('Kopi Sachet 10pcs', 35),
('Sabun Mandi 100g', 60),
('Shampo 200ml', 20),
('Pasta Gigi 150g', 45),
('Deterjen 1kg', 15),
('Air Mineral 600ml', 100);

-- Triggers for automatic cash flow recording
DELIMITER $$

CREATE TRIGGER tr_penjualan_kas_masuk
AFTER INSERT ON penjualan
FOR EACH ROW
BEGIN
    INSERT INTO kas_masuk (id_penjualan, keterangan, tgl_transaksi, jumlah)
    VALUES (NEW.id_penjualan, CONCAT('Penjualan ', NEW.nama_barang), NEW.tgl_jual, NEW.total_penjualan);
    
    UPDATE barang 
    SET stok = stok - NEW.qty,
        updated_at = CURRENT_TIMESTAMP
    WHERE kode_barang = NEW.kode_barang;
    
    INSERT INTO kas (id_kas_masuk, tgl_transaksi, ket_transaksi, saldo)
    VALUES (
        (SELECT id_kas_masuk FROM kas_masuk WHERE id_penjualan = NEW.id_penjualan),
        NEW.tgl_jual,
        CONCAT('Kas Masuk - Penjualan ', NEW.nama_barang),
        (SELECT COALESCE(MAX(saldo), 0) FROM kas) + NEW.total_penjualan
    );
END$$

CREATE TRIGGER tr_pembelian_kas_keluar
AFTER INSERT ON pembelian
FOR EACH ROW
BEGIN
    INSERT INTO kas_keluar (id_pembelian, keterangan, tgl_transaksi, jumlah)
    VALUES (NEW.id_pembelian, CONCAT('Pembelian ', NEW.nama_barang), NEW.tgl_beli, NEW.total_pembelian);
    
    UPDATE barang 
    SET stok = stok + NEW.qty,
        updated_at = CURRENT_TIMESTAMP
    WHERE kode_barang = NEW.kode_barang;
    
    INSERT INTO kas (id_kas_keluar, tgl_transaksi, ket_transaksi, saldo)
    VALUES (
        (SELECT id_kas_keluar FROM kas_keluar WHERE id_pembelian = NEW.id_pembelian),
        NEW.tgl_beli,
        CONCAT('Kas Keluar - Pembelian ', NEW.nama_barang),
        (SELECT COALESCE(MAX(saldo), 0) FROM kas) - NEW.total_pembelian
    );
END$$

DELIMITER ;

-- Views for reporting

CREATE OR REPLACE VIEW v_cash_flow_summary AS
SELECT 
    k.id_kas,
    k.tgl_transaksi,
    k.ket_transaksi,
    CASE WHEN k.id_kas_masuk IS NOT NULL THEN km.jumlah ELSE 0 END as kas_masuk,
    CASE WHEN k.id_kas_keluar IS NOT NULL THEN kk.jumlah ELSE 0 END as kas_keluar,
    k.saldo
FROM kas k
LEFT JOIN kas_masuk km ON k.id_kas_masuk = km.id_kas_masuk
LEFT JOIN kas_keluar kk ON k.id_kas_keluar = kk.id_kas_keluar
ORDER BY k.tgl_transaksi DESC, k.id_kas DESC;

CREATE OR REPLACE VIEW v_purchase_details AS
SELECT 
    p.id_pembelian,
    p.tgl_beli,
    s.nama_supplier,
    b.nama_barang,
    p.qty,
    p.harga,
    p.total_pembelian,
    kk.jumlah as kas_keluar_amount
FROM pembelian p
JOIN supplier s ON p.id_supplier = s.id_supplier
JOIN barang b ON p.kode_barang = b.kode_barang
LEFT JOIN kas_keluar kk ON p.id_pembelian = kk.id_pembelian
ORDER BY p.tgl_beli DESC;

CREATE OR REPLACE VIEW v_sales_details AS
SELECT 
    p.id_penjualan,
    p.tgl_jual,
    b.nama_barang,
    p.qty,
    p.harga,
    p.total_penjualan,
    km.jumlah as kas_masuk_amount
FROM penjualan p
JOIN barang b ON p.kode_barang = b.kode_barang
LEFT JOIN kas_masuk km ON p.id_penjualan = km.id_penjualan
ORDER BY p.tgl_jual DESC;

CREATE OR REPLACE VIEW v_inventory_status AS
SELECT 
    b.kode_barang,
    b.nama_barang,
    b.stok,
    CASE 
        WHEN b.stok <= 5 THEN 'Low Stock'
        WHEN b.stok <= 15 THEN 'Medium Stock'
        ELSE 'Good Stock'
    END as stock_status
FROM barang b
ORDER BY b.stok ASC, b.nama_barang;