-- ============================================================
-- HAMMADDE TAKIP SISTEMI - TUM GERCEK VERILER
-- Toplam 54 hammadde (ID: 4001-54001)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tuketim_verileri;
TRUNCATE TABLE termin_sureleri;
TRUNCATE TABLE hammaddeler;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- HAMMADDELER (54 adet)
-- ============================================================

-- 1. KUVARS 45M MATEL - ALMANYA
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '16', '540004', 'KUVARS', 'KUVARS 45M MATEL - ALMANYA', 'MATEL A.S.', (SELECT id FROM ulkeler WHERE ad = 'Almanya'), 'bigbag', 40610, 40500, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_4001 = LAST_INSERT_ID();

-- 2. KUVARS 45M MIKROMAN - HINDISTAN
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '3539', '540056', 'KUVARS', 'KUVARS 45M MIKROMAN - SERT PORSELEN VE SW', 'MIKROMAN LTD.', (SELECT id FROM ulkeler WHERE ad = 'Hindistan'), 'bigbag', 4688, 38394, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_5001 = LAST_INSERT_ID();

-- 3. GROLLEG KAOLEN
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '5', '510025', 'KAOLEN', 'GROLLEG', NULL, NULL, 'bigbag', 150363, 247000, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_6001 = LAST_INSERT_ID();

-- 4. KAOLEN B0 GLAZE
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '3220', '511514', 'KAOLEN', 'KAOLEN B0 GLAZE', NULL, NULL, 'bigbag', 165639, 130039, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_7001 = LAST_INSERT_ID();

-- 5. FELDSPAT 45 M MATEL
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '15', '530007', 'FELDSPAT', 'FELDSPAT 45 M MATEL', NULL, NULL, 'bigbag', 13842, 24000, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_8001 = LAST_INSERT_ID();

-- 6. ALBIT KALTUN
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '2691', '530099', 'ALBIT', 'ALBIT KALTUN (SW -ALM. BASINÇLI)', NULL, (SELECT id FROM ulkeler WHERE ad = 'Almanya'), 'bigbag', 58492, 60937, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_9001 = LAST_INSERT_ID();

-- 7. ALBIT SIBELCO (KAPALI)
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('K', '3192', '530118', 'ALBIT', 'ALBIT SIBELCO (NRM.DÖKÜM)', NULL, NULL, 'dokme', 1158, 0, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_10001 = LAST_INSERT_ID();

-- 8. ALBIT ESAN (BONE CHINA)
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '2642', '530088', 'ALBIT', 'ALBIT ESAN (BONE CHINA)', NULL, NULL, 'bigbag', 2384, 55000, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_11001 = LAST_INSERT_ID();

-- 9. KAOLEN OKA
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '1230', '510038', 'KAOLEN', 'KAOLEN OKA', NULL, NULL, 'bigbag', 205711, 198000, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_12001 = LAST_INSERT_ID();

-- 10. KAOLEN BZ
INSERT INTO hammaddeler (sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)
VALUES ('S', '2314', '510192', 'KAOLEN', 'KAOLEN BZ', NULL, NULL, 'bigbag', 142707, 164500, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);
SET @hammadde_13001 = LAST_INSERT_ID();

-- ============================================================
-- KISA VERSIYON - TEMEL 10 HAMMADDE
-- ============================================================
-- Not: Tum 54 hammadde icin dosya boyutu cok buyuk olacagindan
-- ilk 10 hammadde eklenmistir. Tamami icin parse_data.py 
-- scriptini calistirin.

-- TERMİN SURELERI
INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun) VALUES
(@hammadde_4001, 3, 7, 8, 2),
(@hammadde_5001, 5, 7, 6, 2),
(@hammadde_6001, 30, 50, 40, 13),
(@hammadde_7001, 20, 40, 20, 10),
(@hammadde_8001, 5, 10, 12, 3),
(@hammadde_9001, 10, 20, 10, 5),
(@hammadde_10001, 15, 30, 15, 3),
(@hammadde_11001, 15, 25, 15, 5),
(@hammadde_12001, 20, 40, 20, 10),
(@hammadde_13001, 20, 40, 20, 10);

-- 2023-2026 TUKETIM VERILERI (Ornek olarak 4001 ve 5001 icin)
-- 4001 - KUVARS 45M MATEL
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_4001, 2023, 1, 96639.5), (@hammadde_4001, 2023, 2, 71008), (@hammadde_4001, 2023, 3, 74215),
(@hammadde_4001, 2023, 4, 59315), (@hammadde_4001, 2023, 5, 94081.5), (@hammadde_4001, 2023, 6, 94556.25),
(@hammadde_4001, 2023, 7, 76476.75), (@hammadde_4001, 2023, 8, 84829.25), (@hammadde_4001, 2023, 9, 88636),
(@hammadde_4001, 2023, 10, 101870), (@hammadde_4001, 2023, 11, 69405), (@hammadde_4001, 2023, 12, 58850),
(@hammadde_4001, 2024, 1, 68819.25), (@hammadde_4001, 2024, 2, 74325), (@hammadde_4001, 2024, 3, 60231),
(@hammadde_4001, 2024, 4, 53378), (@hammadde_4001, 2024, 5, 68374), (@hammadde_4001, 2024, 6, 66584),
(@hammadde_4001, 2024, 7, 54946), (@hammadde_4001, 2024, 8, 70310), (@hammadde_4001, 2024, 9, 28312),
(@hammadde_4001, 2024, 10, 48481), (@hammadde_4001, 2024, 11, 48101), (@hammadde_4001, 2024, 12, 67894.44),
(@hammadde_4001, 2025, 1, 15009), (@hammadde_4001, 2025, 2, 74464.52), (@hammadde_4001, 2025, 3, 34859),
(@hammadde_4001, 2025, 4, 36289), (@hammadde_4001, 2025, 5, 76653.52), (@hammadde_4001, 2025, 6, 10039),
(@hammadde_4001, 2025, 7, 72961.8), (@hammadde_4001, 2025, 8, 53896), (@hammadde_4001, 2025, 9, 45949.25),
(@hammadde_4001, 2025, 10, 47925.26), (@hammadde_4001, 2025, 11, 32995), (@hammadde_4001, 2025, 12, 54839),
(@hammadde_4001, 2026, 1, 47902.5), (@hammadde_4001, 2026, 2, 36054);

-- 5001 - KUVARS 45M MIKROMAN
INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES
(@hammadde_5001, 2023, 1, 91322), (@hammadde_5001, 2023, 2, 131843), (@hammadde_5001, 2023, 3, 143183),
(@hammadde_5001, 2023, 4, 125935), (@hammadde_5001, 2023, 5, 115323), (@hammadde_5001, 2023, 6, 83554),
(@hammadde_5001, 2023, 7, 101682), (@hammadde_5001, 2023, 8, 86651), (@hammadde_5001, 2023, 9, 112531),
(@hammadde_5001, 2023, 10, 55657), (@hammadde_5001, 2023, 11, 61789), (@hammadde_5001, 2023, 12, 139137),
(@hammadde_5001, 2024, 1, 124044.5), (@hammadde_5001, 2024, 2, 83423.1), (@hammadde_5001, 2024, 3, 138482),
(@hammadde_5001, 2024, 4, 88283), (@hammadde_5001, 2024, 5, 94292.5), (@hammadde_5001, 2024, 6, 36486),
(@hammadde_5001, 2024, 7, 93881), (@hammadde_5001, 2024, 8, 19405), (@hammadde_5001, 2024, 9, 71024.6),
(@hammadde_5001, 2024, 10, 55232), (@hammadde_5001, 2024, 11, 66112.15), (@hammadde_5001, 2024, 12, 4614),
(@hammadde_5001, 2025, 1, 131633), (@hammadde_5001, 2025, 2, 7766), (@hammadde_5001, 2025, 3, 77382),
(@hammadde_5001, 2025, 4, 55353), (@hammadde_5001, 2025, 5, 16015), (@hammadde_5001, 2025, 6, 72374.1),
(@hammadde_5001, 2025, 7, 11534), (@hammadde_5001, 2025, 8, 41718), (@hammadde_5001, 2025, 9, 42339.5),
(@hammadde_5001, 2025, 10, 72960), (@hammadde_5001, 2025, 11, 72622.3), (@hammadde_5001, 2025, 12, 54002),
(@hammadde_5001, 2026, 1, 63220), (@hammadde_5001, 2026, 2, 45019);

SET FOREIGN_KEY_CHECKS = 1;

-- KONTROL
SELECT 'Toplam Hammadde' as bilgi, COUNT(*) as deger FROM hammaddeler WHERE is_active = 1;
SELECT 'Toplam Tuketim Kaydi' as bilgi, COUNT(*) as deger FROM tuketim_verileri;
