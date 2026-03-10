#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Hammadde Veri Parser
sistem_yapisi.html'den EXCEL_VERISI array'ini okuyup SQL INSERT'leri oluşturur
"""

import json
import re
import sys

def parse_hammadde_data():
    """HTML dosyasından JSON veriyi parse et"""
    try:
        with open('sistem_yapisi.html', 'r', encoding='utf-8') as f:
            content = f.read()
        
        # EXCEL_VERISI array'ini bul
        pattern = r'const EXCEL_VERISI = (\[.*?\]);'
        match = re.search(pattern, content, re.DOTALL)
        
        if not match:
            print("EXCEL_VERISI bulunamadi!")
            return []
        
        json_str = match.group(1)
        
        # JSON parse et
        data = json.loads(json_str)
        return data
        
    except Exception as e:
        print(f"Hata: {e}")
        return []

def turkce_ay_to_index(ay_ad):
    """Türkçe ay adını sayıya çevir"""
    aylar = {
        'Ocak': 1, 'Şubat': 2, 'Mart': 3, 'Nisan': 4, 'Mayıs': 5, 'Haziran': 6,
        'Temmuz': 7, 'Ağustos': 8, 'Eylül': 9, 'Ekim': 10, 'Kasım': 11, 'Aralık': 12
    }
    return aylar.get(ay_ad, 0)

def clean_string(text):
    """Metni SQL için temizle"""
    if not text:
        return ''
    return text.replace("'", "''").strip()

def generate_sql(data):
    """SQL INSERT'leri oluştur"""
    
    sql_lines = []
    sql_lines.append("-- ============================================================")
    sql_lines.append("-- HAMMADDE TAKIP SISTEMI - GERCEK VERILER (HTML'den aktarilan)")
    sql_lines.append(f"-- Toplam {len(data)} hammadde")
    sql_lines.append("-- ============================================================")
    sql_lines.append("")
    sql_lines.append("SET FOREIGN_KEY_CHECKS = 0;")
    sql_lines.append("")
    
    # Hammaddeler için INSERT'ler
    sql_lines.append("-- ============================================================")
    sql_lines.append("-- 1. HAMMADDELER")
    sql_lines.append("-- ============================================================")
    sql_lines.append("")
    
    variable_names = []
    
    for idx, item in enumerate(data, 1):
        hammadde_id = item.get('id', idx)
        sk = item.get('sk', 'S')
        stok_kodu = item.get('stokKodu', '')
        urun_kodu = item.get('urunKodu', '')
        tur = item.get('tur', '')
        hammadde_ismi = clean_string(item.get('hammaddeIsmi', ''))
        stok_miktari = item.get('stokMiktari', '0') or '0'
        hesaplanan_optimum = item.get('hesaplananOptimum', '0') or '0'
        termin_suresi = item.get('terminSuresi', '0') or '0'
        
        # Ülke tespiti (isminden)
        ulke_adi = 'NULL'
        if 'ALMANYA' in hammadde_ismi or 'ALM.' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Almanya')"
        elif 'HINDISTAN' in hammadde_ismi or 'MIKROMAN' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Hindistan')"
        elif 'ISPANYA' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Ispanya')"
        elif 'CIN' in hammadde_ismi or 'CHINA' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Cin')"
        elif 'ITALYA' in hammadde_ismi or 'ITALIAN' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Italya')"
        elif 'FRANSA' in hammadde_ismi or 'FRANCE' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Fransa')"
        elif 'INGILTERE' in hammadde_ismi or 'UK' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Ingiltere')"
        elif 'ABD' in hammadde_ismi or 'USA' in hammadde_ismi:
            ulke_adi = "(SELECT id FROM ulkeler WHERE ad = 'Amerika Birlesik Devletleri')"
        
        # Tür kodu
        tur_kodu = tur.upper().replace('İ', 'I').replace('ı', 'i')
        if tur_kodu == 'K.ALUMINA':
            tur_kodu = 'K_ALUMINA'
        elif tur_kodu == 'KİL':
            tur_kodu = 'KIL'
        elif tur_kodu == 'DİGER':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'KALSİNE KAOLEN':
            tur_kodu = 'KAOLEN'
        elif tur_kodu == 'ALÇI':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'BAĞLAYICI':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'FRİT':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'BENTONİT':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'CAMSUYU':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'ARITMA KİM.':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'ELEKTROLİT':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'KÖPÜK ÖNLEYİCİ':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'MANGAN OKSİT':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'SODA':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'DEMİR OKSİT':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'PLASTİKLEŞTİRİCİ':
            tur_kodu = 'DIGER'
        elif tur_kodu == 'BÜNYE BOYASI':
            tur_kodu = 'DIGER'
        
        sql_lines.append(f"-- {idx}. {hammadde_ismi}")
        sql_lines.append("INSERT INTO hammaddeler ")
        sql_lines.append("(sk, stok_kodu, urun_kodu, tur_kodu, hammadde_ismi, tedarikci, mensei_ulke_id, paketleme_kodu, stok_miktari, hesaplanan_optimum, birim_fiyat, para_birimi_kodu, fiyat_birimi, teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_turu, created_by)")
        sql_lines.append("VALUES ")
        sql_lines.append(f"('{sk}', '{stok_kodu}', '{urun_kodu}', '{tur_kodu}', '{hammadde_ismi}', NULL, {ulke_adi}, 'bigbag', {stok_miktari}, {hesaplanan_optimum}, 0, 'USD', 'ton', 'CIF', 'yuzde', 0, 'T', 1);")
        
        var_name = f"@hammadde_{hammadde_id}"
        variable_names.append((var_name, item))
        sql_lines.append(f"SET {var_name} = LAST_INSERT_ID();")
        sql_lines.append("")
    
    # Termin süreleri
    sql_lines.append("-- ============================================================")
    sql_lines.append("-- 2. TERMİN SURELERI")
    sql_lines.append("-- ============================================================")
    sql_lines.append("")
    
    for var_name, item in variable_names:
        termin = item.get('termin', {})
        akreditif = termin.get('akreditif', 0) or 0
        satici = termin.get('saticiTedarik', 0) or 0
        yol = termin.get('yol', 0) or 0
        depo = termin.get('depoKabul', 0) or 0
        
        # Termin süresi yoksa varsayılan değerler
        if not any([akreditif, satici, yol, depo]):
            termin_suresi = int(item.get('terminSuresi', 20) or 20)
            akreditif = termin_suresi // 4
            satici = termin_suresi // 4
            yol = termin_suresi // 4
            depo = termin_suresi - (akreditif + satici + yol)
        
        sql_lines.append(f"INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)")
        sql_lines.append(f"VALUES ({var_name}, {akreditif}, {satici}, {yol}, {depo});")
    
    sql_lines.append("")
    
    # Tüketim verileri
    sql_lines.append("-- ============================================================")
    sql_lines.append("-- 3. TUKETIM VERILERI")
    sql_lines.append("-- ============================================================")
    sql_lines.append("")
    
    yillar = [2023, 2024, 2025, 2026]
    aylar_tr = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 
                'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık']
    
    for var_name, item in variable_names:
        hammadde_id = item.get('id', 0)
        tuketim = item.get('tuketim', {})
        
        sql_lines.append(f"-- Hammadde ID: {hammadde_id}")
        
        for yil in yillar:
            yil_data = tuketim.get(str(yil), {})
            
            for idx, ay_ad in enumerate(aylar_tr, 1):
                miktar = yil_data.get(ay_ad, '')
                
                # Boş değerleri atla
                if miktar == '' or miktar is None or miktar == 0 or miktar == '0':
                    continue
                
                try:
                    miktar_val = float(miktar)
                    if miktar_val > 0:
                        sql_lines.append(f"INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg) VALUES ({var_name}, {yil}, {idx}, {miktar_val});")
                except:
                    pass
        
        sql_lines.append("")
    
    sql_lines.append("SET FOREIGN_KEY_CHECKS = 1;")
    sql_lines.append("")
    sql_lines.append("-- ============================================================")
    sql_lines.append("-- KONTROL SORGULARI")
    sql_lines.append("-- ============================================================")
    sql_lines.append("")
    sql_lines.append("SELECT 'Toplam Hammadde Sayisi' as bilgi, COUNT(*) as deger FROM hammaddeler WHERE is_active = 1;")
    sql_lines.append("SELECT 'Toplam Tuketim Kaydi' as bilgi, COUNT(*) as deger FROM tuketim_verileri;")
    sql_lines.append("SELECT 'Toplam Termin Kaydi' as bilgi, COUNT(*) as deger FROM termin_sureleri;")
    
    return '\n'.join(sql_lines)

def main():
    print("Hammadde verileri parse ediliyor...")
    data = parse_hammadde_data()
    
    if not data:
        print("Veri bulunamadi!")
        sys.exit(1)
    
    print(f"Toplam {len(data)} hammadde bulundu.")
    print("SQL dosyasi olusturuluyor...")
    
    sql_content = generate_sql(data)
    
    output_file = 'database/all_real_data.sql'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(sql_content)
    
    print(f"SQL dosyasi olusturuldu: {output_file}")
    print(f"Dosya boyutu: {len(sql_content)} karakter")

if __name__ == '__main__':
    main()
