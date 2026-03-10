-- Hammadde Takip Sistemi - Veritabani Semasi
-- MySQL/MariaDB uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- 1. KULLANICILAR TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayilan admin kullanici (sifre: admin123)
INSERT INTO users (username, password_hash, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@system.local', 'Sistem Yoneticisi', 'admin');

-- ========================================
-- 2. HAMMADDE TURLERI
-- ========================================
CREATE TABLE IF NOT EXISTS hammadde_turleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(20) NOT NULL UNIQUE,
    ad VARCHAR(50) NOT NULL,
    sira INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hammadde_turleri (kod, ad, sira) VALUES 
('KUVARS', 'Kuvars', 1),
('KAOLEN', 'Kaolen', 2),
('FELDSPAT', 'Feldspat', 3),
('ALBIT', 'Albit', 4),
('KIL', 'Kil', 5),
('K_ALUMINA', 'K.Alumina', 6),
('TALKIT', 'Talkit', 7),
('DOLOMIT', 'Dolomit', 8),
('DIGER', 'Diger', 99);

-- ========================================
-- 3. PARA BIRIMLERI
-- ========================================
CREATE TABLE IF NOT EXISTS para_birimleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(3) NOT NULL UNIQUE,
    ad VARCHAR(50) NOT NULL,
    sembol VARCHAR(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO para_birimleri (kod, ad, sembol) VALUES 
('USD', 'Amerikan Dolari', '$'),
('EUR', 'Euro', '€'),
('GBP', 'Ingiliz Sterlini', '£'),
('TRY', 'Turk Lirasi', '₺'),
('CHF', 'Isvicre Frangi', 'Fr');

-- ========================================
-- 4. TESLIMAT SEKILLERI
-- ========================================
CREATE TABLE IF NOT EXISTS teslimat_sekilleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(3) NOT NULL UNIQUE,
    ad VARCHAR(100) NOT NULL,
    aciklama TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO teslimat_sekilleri (kod, ad, aciklama) VALUES 
('EXW', 'EXW - Ex Works', 'Satici mali kendi deposunda teslim eder. Tum nakliye, sigorta ve gumruk masraflari aliciya aittir.'),
('FCA', 'FCA - Free Carrier', 'Satici mali belirlenen noktada tasiyiciya teslim eder.'),
('CPT', 'CPT - Carriage Paid To', 'Satici nakliyeyi belirlenen varis yerine kadar oder.'),
('CIP', 'CIP - Carriage & Insurance Paid', 'CPT ye ek olarak satici sigorta yaptirmakla yukumludur.'),
('DAP', 'DAP - Delivered at Place', 'Satici mali belirlenen varis yerine teslim eder.'),
('DPU', 'DPU - Delivered at Place Unloaded', 'Satici mali varis yerinde bosaltarak teslim eder.'),
('DDP', 'DDP - Delivered Duty Paid', 'Satici tum masraflari karsilar ve mali teslim eder.'),
('FAS', 'FAS - Free Alongside Ship', 'Deniz tasimaciligi. Satici mali yukleme limaninda gemi bordasina teslim eder.'),
('FOB', 'FOB - Free on Board', 'Deniz tasimaciligi. Satici mali gemi guvertesine yukler.'),
('CFR', 'CFR - Cost & Freight', 'Deniz tasimaciligi. Satici navlunu oder, sigorta aliciya aittir.'),
('CIF', 'CIF - Cost, Insurance & Freight', 'Deniz tasimaciligi. Satici navlun ve sigortayi oder.');

-- ========================================
-- 5. PAKETLEME TIPLERI
-- ========================================
CREATE TABLE IF NOT EXISTS paketleme_tipleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(20) NOT NULL UNIQUE,
    ad VARCHAR(100) NOT NULL,
    aciklama TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO paketleme_tipleri (kod, ad, aciklama) VALUES 
('dokme', 'Dokme', 'Tanker, silo veya acik aracla dokme tasima'),
('bigbag', 'Big Bag (FIBC)', '500-1500 kg lik jumbo torba, vinc ile elleclenir'),
('craft', 'Kraft Kagit Torba', '25-50 kg lik cok katli kagit ambalaj'),
('plastik', 'Plastik Torba', '25-50 kg lik PE/PP torba'),
('drum', 'Varil / Drum', 'Metal veya plastik 200 lt / 250 kg varil'),
('ibc', 'IBC Konteyner', '1000 lt lik palet uzeri sivi konteyner'),
('teneke', 'Teneke / Bidon', '5-25 kg metal veya plastik ambalaj'),
('karton', 'Karton Kutu', 'Kucuk miktarlar icin oluklu mukavva ambalaj'),
('konteyner', 'ISO Konteyner', '20 veya 40 FCL konteyner icinde');

-- ========================================
-- 6. ULKELER
-- ========================================
CREATE TABLE IF NOT EXISTS ulkeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ulkeler (ad) VALUES 
('Afganistan'), ('Almanya'), ('Amerika Birlesik Devletleri'), ('Angola'), ('Arjantin'), 
('Arnavutluk'), ('Avustralya'), ('Avusturya'), ('Azerbaycan'), ('Bahreyn'), 
('Banglades'), ('Belcika'), ('Beyaz Rusya'), ('Brezilya'), ('Bulgaristan'), 
('Cezayir'), ('Cek Cumhuriyeti'), ('Cin'), ('Danimarka'), ('Endonezya'), 
('Ermenistan'), ('Fas'), ('Filipinler'), ('Finlandiya'), ('Fransa'), 
('Guney Afrika'), ('Guney Kore'), ('Gurcistan'), ('Hindistan'), ('Hollanda'), 
('Irak'), ('Ingiltere'), ('Iran'), ('Irlanda'), ('Ispanya'), 
('Isvec'), ('Isvicre'), ('Italya'), ('Izlanda'), ('Japonya'), 
('Kanada'), ('Kazakistan'), ('Katar'), ('Kolombiya'), ('Kuveyt'), 
('Kuba'), ('Libya'), ('Macaristan'), ('Makedonya'), ('Malezya'), 
('Meksika'), ('Misir'), ('Moldova'), ('Mogolistan'), ('Nijerya'), 
('Norvec'), ('Ozbekistan'), ('Pakistan'), ('Polonya'), ('Portekiz'), 
('Romanya'), ('Rusya'), ('Suudi Arabistan'), ('Sirbistan'), ('Singapur'), 
('Slovakya'), ('Slovenya'), ('Sudan'), ('Suriye'), ('Tacikistan'), 
('Tayland'), ('Tunus'), ('Turkiye'), ('Turkmenistan'), ('Ukrayna'), 
('Umman'), ('Urdun'), ('Venezuela'), ('Vietnam'), ('Yemen'), 
('Yeni Zelanda'), ('Yunanistan');

-- ========================================
-- 7. HAMMADDE ANA TABLOSU
-- ========================================
CREATE TABLE IF NOT EXISTS hammaddeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sk ENUM('S', 'K', 'A') NOT NULL DEFAULT 'S' COMMENT 'S:Standart, K:Kapali, A:Alternatif',
    stok_kodu VARCHAR(20) NULL,
    urun_kodu VARCHAR(20) NULL,
    tur_kodu VARCHAR(20) NULL,
    hammadde_ismi VARCHAR(255) NOT NULL,
    tedarikci VARCHAR(255) NULL,
    mensei_ulke_id INT NULL,
    paketleme_kodu VARCHAR(20) NULL,
    stok_miktari DECIMAL(15, 2) DEFAULT 0 COMMENT 'kg cinsinden',
    hesaplanan_optimum DECIMAL(15, 2) DEFAULT 0,
    termin_suresi_gun INT DEFAULT 0,
    birim_fiyat DECIMAL(15, 4) DEFAULT 0,
    para_birimi_kodu VARCHAR(3) DEFAULT 'USD',
    fiyat_birimi ENUM('kg', 'ton') DEFAULT 'ton',
    teslimat_sekli_kodu VARCHAR(3) DEFAULT 'CIF',
    maliyet_tipi ENUM('yuzde', 'tutar') DEFAULT 'yuzde',
    maliyet_deger DECIMAL(15, 4) DEFAULT 0,
    maliyet_pb_kodu VARCHAR(3) NULL,
    maliyet_turu ENUM('T', 'G') DEFAULT 'T' COMMENT 'T:Tahmini, G:Gerceklesen',
    alternatif TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (tur_kodu) REFERENCES hammadde_turleri(kod),
    FOREIGN KEY (mensei_ulke_id) REFERENCES ulkeler(id),
    FOREIGN KEY (paketleme_kodu) REFERENCES paketleme_tipleri(kod),
    FOREIGN KEY (para_birimi_kodu) REFERENCES para_birimleri(kod),
    FOREIGN KEY (teslimat_sekli_kodu) REFERENCES teslimat_sekilleri(kod),
    FOREIGN KEY (maliyet_pb_kodu) REFERENCES para_birimleri(kod),
    INDEX idx_stok_kodu (stok_kodu),
    INDEX idx_tur (tur_kodu),
    INDEX idx_sk (sk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 8. TERMİN SURELERI
-- ========================================
CREATE TABLE IF NOT EXISTS termin_sureleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hammadde_id INT NOT NULL,
    akreditif_gun INT DEFAULT 0,
    satici_tedarik_gun INT DEFAULT 0,
    yol_gun INT DEFAULT 0,
    depo_kabul_gun INT DEFAULT 0,
    FOREIGN KEY (hammadde_id) REFERENCES hammaddeler(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hammadde_termin (hammadde_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 9. TUKETIM VERILERI
-- ========================================
CREATE TABLE IF NOT EXISTS tuketim_verileri (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    hammadde_id INT NOT NULL,
    yil INT NOT NULL,
    ay TINYINT NOT NULL CHECK (ay BETWEEN 1 AND 12),
    miktar_kg DECIMAL(15, 2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hammadde_id) REFERENCES hammaddeler(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hammadde_yil_ay (hammadde_id, yil, ay),
    INDEX idx_yil_ay (yil, ay)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 10. FIYAT GECMISI
-- ========================================
CREATE TABLE IF NOT EXISTS fiyat_gecmisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hammadde_id INT NOT NULL,
    birim_fiyat DECIMAL(15, 4) NOT NULL,
    para_birimi_kodu VARCHAR(3) NOT NULL,
    fiyat_birimi ENUM('kg', 'ton') NOT NULL,
    teslimat_sekli_kodu VARCHAR(3) NULL,
    maliyet_tipi ENUM('yuzde', 'tutar') NULL,
    maliyet_deger DECIMAL(15, 4) NULL,
    maliyet_pb_kodu VARCHAR(3) NULL,
    maliyet_turu ENUM('T', 'G') NULL,
    kayit_tarihi DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hammadde_id) REFERENCES hammaddeler(id) ON DELETE CASCADE,
    INDEX idx_hammadde_tarih (hammadde_id, kayit_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 11. SIPARISLER
-- ========================================
CREATE TABLE IF NOT EXISTS siparisler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hammadde_id INT NOT NULL,
    siparis_no VARCHAR(50) NULL,
    miktar_kg DECIMAL(15, 2) NOT NULL,
    tarih DATE NOT NULL,
    verildi TINYINT(1) DEFAULT 1,
    geldi TINYINT(1) DEFAULT 0,
    teslim_tarihi DATE NULL,
    notlar TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hammadde_id) REFERENCES hammaddeler(id) ON DELETE CASCADE,
    INDEX idx_geldi (geldi),
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 12. DOVIZ KURLARI
-- ========================================
CREATE TABLE IF NOT EXISTS doviz_kurlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kaynak_pb VARCHAR(3) NOT NULL,
    hedef_pb VARCHAR(3) NOT NULL,
    kur DECIMAL(15, 6) NOT NULL,
    tarih DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kur_tarih (kaynak_pb, hedef_pb, tarih),
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
