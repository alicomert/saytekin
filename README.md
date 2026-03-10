# Hammadde Takip Sistemi

Modern, esnek ve dinamik PHP/MySQL tabanli hammadde yonetim sistemi. Tailwind CSS ile modern arayuz.

## Özellikler

### Temel Özellikler
- ✅ Kullanici girisi (Session - 1 hafta)
- ✅ Hammadde CRUD islemleri
- ✅ Tuketim verileri (Yil/Ay bazli)
- ✅ Stok takibi ve kritik seviye uyarilari
- ✅ Fiyat ve maliyet yonetimi
- ✅ Termin suresi takibi (4 asamali)
- ✅ Siparis yonetimi
- ✅ Doviz kuru entegrasyonu (API)
- ✅ Fiyat karsilastirma
- ✅ Stok guncelleme toplu mod

### Hammadde Turleri
- Standart (S)
- Kapali (K)
- Alternatif (A)

### Raporlama & Analiz
- Yillik tuketim ortalamalari
- Son 12 ay / Son 3 ay ortalama
- Stok/Termin orani hesaplama
- Kritik stok listesi
- Fiyat gecmisi takibi

## Kurulum

### 1. Veritabani Olusturma

```bash
# MySQL/MariaDB'e baglan
mysql -u root -p

# Veritabani olustur
CREATE DATABASE hammadde_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hammadde_takip;

# Tablolari olustur
source database/schema.sql

# (Opsiyonel) Ornek veriler
source database/sample_data.sql
```

### 2. Config Ayarlari

`includes/config.php` dosyasinda veritabani bilgilerini guncelleyin:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hammadde_takip');
define('DB_USER', 'root');
define('DB_PASS', 'sifreniz');
```

### 3. Klasor Yapisi

```
hammadde-takip/
├── ajax/                   # AJAX islemleri
├── database/              
│   ├── schema.sql         # Veritabani semasi
│   └── sample_data.sql    # Ornek veriler
├── includes/
│   ├── config.php         # Ana config
│   ├── functions.php      # Fonksiyonlar
│   ├── header.php         # Header sablonu
│   └── footer.php         # Footer sablonu
├── index.php              # Ana liste
├── login.php              # Giris
├── logout.php             # Cikis
├── hammadde-form.php      # Ekle/Duzenle
├── hammadde-detay.php     # Detay
├── ihtiyac.php            # Ihtiyac listesi
├── siparisler.php         # Siparisler
├── fiyatlar.php           # Fiyat tablosu
├── karsilastirma.php      # Karsilastirma
└── stok-guncelle.php      # Stok guncelleme
```

## Kullanim

### Varsayilan Giris Bilgileri
- **Kullanici Adi:** admin
- **Sifre:** admin123

⚠️ **Güvenlik:** Üretim ortaminda sifreyi degistirin!

### Hammadde Ekleme
1. Sag ustte "Yeni Hammadde" butonuna tiklayin
2. Zorunlu alanlari doldurun (S/K, Hammadde Ismi)
3. Termin surelerini girin
4. Fiyat bilgilerini ekleyin
5. Tuketim verilerini yil/ay bazinda girin
6. "Kaydet" butonuna tiklayin

### Stok Guncelleme
1. "Stok Guncelle" menusune gidin
2. Yeni stok miktarlarini girin
3. Guncel ay tuketimini girin
4. "Tum Degisiklikleri Kaydet" butonuna tiklayin

### Ihtiyac Listesi
- Otomatik olarak kritik stok seviyesindeki hammaddeleri listeler
- Stok/Termin orani < 2 olan hammaddeleri gosterir
- Acil siparis, Siparis Ver, Takipte durumlarini renklendirir

## Teknik Detaylar

### Veritabani Tablolari
- **users:** Kullanicilar
- **hammaddeler:** Hammadde ana tablosu
- **tuketim_verileri:** Aylik tuketim verileri
- **termin_sureleri:** Termin sureleri (4 asama)
- **fiyat_gecmisi:** Fiyat degisiklik kayitlari
- **siparisler:** Siparis kayitlari
- **doviz_kurlari:** Doviz kuru cache
- **hammadde_turleri, ulkeler, paketleme_tipleri, teslimat_sekilleri, para_birimleri:** Referans tablolari

### Güvenlik
- Session yonetimi (1 hafta)
- XSS korumasi (htmlspecialchars)
- SQL injection korumasi (PDO prepared statements)
- CSRF token destegi (hazir)

### Performans
- Index'ler optimizasyon icin eklenmistir
- Doviz kurlari cache'lenir (6 saat)

## Lisans

Bu proje MIT lisansi ile lisanslanmistir.

## Gelistirici

Hammadde Takip Sistemi v1.0
