-- ============================================================
-- HAMMADDE TAKIP SISTEMI - GERCEK VERILER (HTML'den aktarilan)
-- ============================================================
-- Bu dosya sistem_yapisi.html'deki EXCEL_VERISI iceriginden otomatik uretilmistir

-- Once tablolari temizle (eger varsa)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE tuketim_verileri;
-- TRUNCATE TABLE termin_sureleri;
-- TRUNCATE TABLE hammaddeler;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. HAMMADDE: KUVARS 45M MATEL - ALMANYA
-- ============================================================
INSERT INTO hammaddeler 
(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES 
('S', '16', '540004', 'KUVARS', 'KUVARS 45M MATEL - ALMANYA', 'MATEL A.S.', 
 (SELECT id FROM ulkeler WHERE ad = 'Almanya'), 'bigbag', 40610.00, 40500.00, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);

SET @hammadde_4001 = LAST_INSERT_ID();

-- Termin sureleri (20 gun toplam - varsayilan dagilim)
INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)
VALUES (@hammadde_4001, 3, 7, 8, 2);

-- 2023 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_4001, 2023, 1, 96639.50),   -- Ocak
(@hammadde_4001, 2023, 2, 71008.00),   -- Subat
(@hammadde_4001, 2023, 3, 74215.00),   -- Mart
(@hammadde_4001, 2023, 4, 59315.00),   -- Nisan
(@hammadde_4001, 2023, 5, 94081.50),   -- Mayis
(@hammadde_4001, 2023, 6, 94556.25),   -- Haziran
(@hammadde_4001, 2023, 7, 76476.75),   -- Temmuz
(@hammadde_4001, 2023, 8, 84829.25),   -- Agustos
(@hammadde_4001, 2023, 9, 88636.00),   -- Eylul
(@hammadde_4001, 2023, 10, 101870.00), -- Ekim
(@hammadde_4001, 2023, 11, 69405.00),  -- Kasim
(@hammadde_4001, 2023, 12, 58850.00);  -- Aralik

-- 2024 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_4001, 2024, 1, 68819.25),   -- Ocak
(@hammadde_4001, 2024, 2, 74325.00),   -- Subat
(@hammadde_4001, 2024, 3, 60231.00),   -- Mart
(@hammadde_4001, 2024, 4, 53378.00),   -- Nisan
(@hammadde_4001, 2024, 5, 68374.00),   -- Mayis
(@hammadde_4001, 2024, 6, 66584.00),   -- Haziran
(@hammadde_4001, 2024, 7, 54946.00),   -- Temmuz
(@hammadde_4001, 2024, 8, 70310.00),   -- Agustos
(@hammadde_4001, 2024, 9, 28312.00),   -- Eylul
(@hammadde_4001, 2024, 10, 48481.00),  -- Ekim
(@hammadde_4001, 2024, 11, 48101.00),  -- Kasim
(@hammadde_4001, 2024, 12, 67894.44);  -- Aralik

-- 2025 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_4001, 2025, 1, 15009.00),   -- Ocak
(@hammadde_4001, 2025, 2, 74464.52),   -- Subat
(@hammadde_4001, 2025, 3, 34859.00),   -- Mart
(@hammadde_4001, 2025, 4, 36289.00),   -- Nisan
(@hammadde_4001, 2025, 5, 76653.52),   -- Mayis
(@hammadde_4001, 2025, 6, 10039.00),   -- Haziran
(@hammadde_4001, 2025, 7, 72961.80),   -- Temmuz
(@hammadde_4001, 2025, 8, 53896.00),   -- Agustos
(@hammadde_4001, 2025, 9, 45949.25),   -- Eylul
(@hammadde_4001, 2025, 10, 47925.26),  -- Ekim
(@hammadde_4001, 2025, 11, 32995.00),  -- Kasim
(@hammadde_4001, 2025, 12, 54839.00);  -- Aralik

-- 2026 Tuketim Verileri (sadece Ocak ve Subat dolu)
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_4001, 2026, 1, 47902.50),   -- Ocak
(@hammadde_4001, 2026, 2, 36054.00);   -- Subat
-- Mart-Aralik 2026 bos (veri girilmemis)


-- ============================================================
-- 2. HAMMADDE: KUVARS 45M MIKROMAN - SERT PORSELEN VE SW
-- ============================================================
INSERT INTO hammaddeler 
(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES 
('S', '3539', '540056', 'KUVARS', 'KUVARS 45M MIKROMAN - SERT PORSELEN VE SW', 'MIKROMAN LTD.', 
 (SELECT id FROM ulkeler WHERE ad = 'Hindistan'), 'bigbag', 4688.00, 38394.00, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);

SET @hammadde_5001 = LAST_INSERT_ID();

-- Termin sureleri (20 gun toplam - varsayilan dagilim)
INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)
VALUES (@hammadde_5001, 5, 7, 6, 2);

-- 2023 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_5001, 2023, 1, 91322.00),   -- Ocak
(@hammadde_5001, 2023, 2, 131843.00),  -- Subat
(@hammadde_5001, 2023, 3, 143183.00),  -- Mart
(@hammadde_5001, 2023, 4, 125935.00),  -- Nisan
(@hammadde_5001, 2023, 5, 115323.00),  -- Mayis
(@hammadde_5001, 2023, 6, 83554.00),   -- Haziran
(@hammadde_5001, 2023, 7, 101682.00),  -- Temmuz
(@hammadde_5001, 2023, 8, 86651.00),   -- Agustos
(@hammadde_5001, 2023, 9, 112531.00),  -- Eylul
(@hammadde_5001, 2023, 10, 55657.00),  -- Ekim
(@hammadde_5001, 2023, 11, 61789.00),  -- Kasim
(@hammadde_5001, 2023, 12, 139137.00); -- Aralik

-- 2024 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_5001, 2024, 1, 124044.50),  -- Ocak
(@hammadde_5001, 2024, 2, 83423.10),   -- Subat
(@hammadde_5001, 2024, 3, 138482.00),  -- Mart
(@hammadde_5001, 2024, 4, 88283.00),   -- Nisan
(@hammadde_5001, 2024, 5, 94292.50),   -- Mayis
(@hammadde_5001, 2024, 6, 36486.00),   -- Haziran
(@hammadde_5001, 2024, 7, 93881.00),   -- Temmuz
(@hammadde_5001, 2024, 8, 19405.00),   -- Agustos
(@hammadde_5001, 2024, 9, 71024.60),   -- Eylul
(@hammadde_5001, 2024, 10, 55232.00),  -- Ekim
(@hammadde_5001, 2024, 11, 66112.15),  -- Kasim
(@hammadde_5001, 2024, 12, 4614.00);   -- Aralik

-- 2025 Tuketim Verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_5001, 2025, 1, 131633.00),  -- Ocak
(@hammadde_5001, 2025, 2, 7766.00),    -- Subat
(@hammadde_5001, 2025, 3, 77382.00),   -- Mart
(@hammadde_5001, 2025, 4, 55353.00),   -- Nisan
(@hammadde_5001, 2025, 5, 16015.00),   -- Mayis
(@hammadde_5001, 2025, 6, 72374.10),   -- Haziran
(@hammadde_5001, 2025, 7, 11534.00),   -- Temmuz
(@hammadde_5001, 2025, 8, 41718.00),   -- Agustos
(@hammadde_5001, 2025, 9, 42339.50),   -- Eylul
(@hammadde_5001, 2025, 10, 72960.00),  -- Ekim
(@hammadde_5001, 2025, 11, 72622.30),  -- Kasim
(@hammadde_5001, 2025, 12, 54002.00);  -- Aralik

-- 2026 Tuketim Verileri (sadece Ocak ve Subat dolu)
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_5001, 2026, 1, 63220.00),   -- Ocak
(@hammadde_5001, 2026, 2, 45019.00);   -- Subat
-- Mart-Aralik 2026 bos (veri girilmemis)


-- ============================================================
-- KONTROL SORGULARI
-- ============================================================

-- Eklenen hammaddeleri kontrol et
SELECT 
    h.id,
    h.sk,
    h.stok_kodu,
    h.hammadde_ismi,
    h.stok_miktari,
    h.hesaplanan_optimum,
    t.akreditif_gun + t.satici_tedarik_gun + t.yol_gun + t.depo_kabul_gun as toplam_termin,
    (SELECT COUNT(*) FROM tuketim_verileri WHERE hammadde_id = h.id) as tuketim_kayit_sayisi
FROM hammaddeler h
LEFT JOIN termin_sureleri t ON h.id = t.hammadde_id
WHERE h.id IN (@hammadde_4001, @hammadde_5001);

-- Yillik toplam tuketimler
SELECT 
    h.hammadde_ismi,
    tv.yil,
    SUM(tv.miktar_kg) as yillik_toplam,
    AVG(tv.miktar_kg) as aylik_ortalama
FROM tuketim_verileri tv
JOIN hammaddeler h ON tv.hammadde_id = h.id
WHERE tv.miktar_kg IS NOT NULL
GROUP BY h.hammadde_ismi, tv.yil
ORDER BY h.id, tv.yil;
