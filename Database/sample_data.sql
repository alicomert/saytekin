-- Hammadde Takip Sistemi - Ornek Veriler
-- Bu veriler test amaclidir

-- Hammadde turlerini kontrol et
SELECT 'Hammadde turleri yuklendi' as mesaj;

-- Para birimlerini kontrol et
SELECT 'Para birimleri yuklendi' as mesaj;

-- Ulkeleri kontrol et
SELECT 'Ulkeler yuklendi' as mesaj;

-- Teslimat sekillerini kontrol et
SELECT 'Teslimat sekilleri yuklendi' as mesaj;

-- Paketleme tiplerini kontrol et
SELECT 'Paketleme tipleri yuklendi' as mesaj;

-- ========================================
-- ORNEK HAMMADDELER (Isterseniz calistirin)
-- ========================================

/*
-- Almanya'dan Kuvvars 45M
INSERT INTO hammaddeler 
(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu)
VALUES 
('S', '16', '540004', 'KUVARS', 'KUVARS 45M MATEL - ALMANYA', 'MATEL A.S.', 
 (SELECT id FROM ulkeler WHERE ad = 'Almanya'), 'bigbag', 40610, 40500, 125.50, 'USD', 'ton', 'CIF', 'yuzde', 15, 'T');

SET @hammadde_id1 = LAST_INSERT_ID();

-- Termin sureleri
INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)
VALUES (@hammadde_id1, 3, 7, 14, 2);

-- 2023 tuketim verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_id1, 2023, 1, 96639.5), (@hammadde_id1, 2023, 2, 71008), (@hammadde_id1, 2023, 3, 74215),
(@hammadde_id1, 2023, 4, 59315), (@hammadde_id1, 2023, 5, 94081.5), (@hammadde_id1, 2023, 6, 94556.25),
(@hammadde_id1, 2023, 7, 76476.75), (@hammadde_id1, 2023, 8, 84829.25), (@hammadde_id1, 2023, 9, 88636),
(@hammadde_id1, 2023, 10, 101870), (@hammadde_id1, 2023, 11, 69405), (@hammadde_id1, 2023, 12, 58850);

-- 2024 tuketim verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_id1, 2024, 1, 68819.25), (@hammadde_id1, 2024, 2, 74325), (@hammadde_id1, 2024, 3, 60231),
(@hammadde_id1, 2024, 4, 53378), (@hammadde_id1, 2024, 5, 68374), (@hammadde_id1, 2024, 6, 66584),
(@hammadde_id1, 2024, 7, 54946), (@hammadde_id1, 2024, 8, 70310), (@hammadde_id1, 2024, 9, 28312),
(@hammadde_id1, 2024, 10, 48481), (@hammadde_id1, 2024, 11, 48101), (@hammadde_id1, 2024, 12, 67894.44);

-- 2025 tuketim verileri
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_id1, 2025, 1, 15009), (@hammadde_id1, 2025, 2, 74464.52), (@hammadde_id1, 2025, 3, 34859),
(@hammadde_id1, 2025, 4, 36289), (@hammadde_id1, 2025, 5, 76653.52), (@hammadde_id1, 2025, 6, 10039);

-- Hindistan'dan Kuvvars
INSERT INTO hammaddeler 
(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu)
VALUES 
('S', '3539', '540056', 'KUVARS', 'KUVARS 45M MIKROMAN - HINDISTAN', 'MIKROMAN LTD.', 
 (SELECT id FROM ulkeler WHERE ad = 'Hindistan'), 'bigbag', 4688, 38394, 98.75, 'USD', 'ton', 'CIF', 'yuzde', 12, 'T');

SET @hammadde_id2 = LAST_INSERT_ID();

INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)
VALUES (@hammadde_id2, 5, 10, 21, 3);

-- Alternatif Kaolen
INSERT INTO hammaddeler 
(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, alternatif, birim_fiyat, para_birimi_kodu, fiyat_birimi)
VALUES 
('A', NULL, NULL, 'KAOLEN', 'KAOLEN ALTERNATIF - YERLI', 'YERLI TEDARIKCI', 
 (SELECT id FROM ulkeler WHERE ad = 'Turkiye'), 'dokme', 25000, 1, 45.00, 'TRY', 'ton');

SELECT 'Ornek veriler eklendi' as mesaj;
*/

SELECT 'Kurulum tamamlandi. Ornek verileri eklemek icin yukaridaki yorumlari kaldirin ve calistirin.' as mesaj;
