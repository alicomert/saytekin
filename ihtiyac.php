<?php
require_once 'includes/header.php';

$pageTitle = 'İhtiyaç Listesi';
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; }
.skeleton { background: linear-gradient(90deg, #1e2430 25%, #2d3748 50%, #1e2430 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
@keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;" id="ihtiyac-container">
    <!-- Header -->
    <div style="margin-bottom:20px;">
        <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">⚠️ İhtiyaç Listesi</h2>
        <p style="color:#475569;font-size:13px;">Stok miktarı hesaplanan optimumun altında kalan hammaddeler</p>
    </div>

    <!-- Özet Kartlar - Dinamik -->
    <div id="ozet-kartlar" style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
        <!-- JavaScript ile doldurulacak -->
        <div class="skeleton" style="height:100px;border-radius:12px;"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;"></div>
    </div>

    <!-- Tablo Container -->
    <div id="tablo-container">
        <div class="skeleton" style="height:400px;border-radius:12px;"></div>
    </div>
</div>

<script>
// Global state
let ihtiyacData = [];
let siparisFormAcik = {};

// Verileri çek
function verileriYukle() {
    fetch('ajax/ihtiyac-veriler.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                ihtiyacData = data.data;
                ozetKartlariGuncelle(data.ozet);
                tabloyuGuncelle();
            }
        })
        .catch(err => {
            console.error('Veri yükleme hatası:', err);
            document.getElementById('tablo-container').innerHTML = `
                <div style="text-align:center;padding:40px;color:#ef4444;">
                    <div style="font-size:24px;margin-bottom:8px;">⚠️</div>
                    <div>Veriler yüklenirken bir hata oluştu.</div>
                    <button onclick="verileriYukle()" style="margin-top:16px;padding:8px 16px;background:#1e2430;border:1px solid #3b82f655;border-radius:6px;color:#60a5fa;cursor:pointer;">Tekrar Dene</button>
                </div>
            `;
        });
}

// Özet kartlarını güncelle
function ozetKartlariGuncelle(ozet) {
    const kartlar = [
        { label: 'Toplam İhtiyaç', val: ozet.toplam + ' kalem', renk: '#f59e0b', bg: '#2a1f0a', icon: '📦' },
        { label: 'Siparişi Verilmiş', val: ozet.siparisVerilmis + ' kalem', renk: '#34d399', bg: '#0d2018', icon: '✅' },
        { label: 'Acil Sipariş (<%50)', val: ozet.acil + ' kalem', renk: '#ef4444', bg: '#2d1a1a', icon: '🚨' },
        { label: 'Sipariş Ver (50-100%)', val: ozet.siparisVer + ' kalem', renk: '#f97316', bg: '#2a1a0a', icon: '📦' },
        { label: 'Takipte (>100%)', val: ozet.takipte + ' kalem', renk: '#eab308', bg: '#1f1e0a', icon: '👁' },
    ];
    
    document.getElementById('ozet-kartlar').innerHTML = kartlar.map(k => `
        <div style="background:${k.bg};border:1px solid ${k.renk}44;border-radius:12px;padding:18px;border-top:3px solid ${k.renk};">
            <div style="font-size:22px;margin-bottom:8px;">${k.icon}</div>
            <div style="font-size:22px;font-weight:700;color:${k.renk};">${k.val}</div>
            <div style="font-size:11px;color:#64748b;margin-top:4px;">${k.label}</div>
        </div>
    `).join('');
}

// Tabloyu güncelle
function tabloyuGuncelle() {
    if (ihtiyacData.length === 0) {
        document.getElementById('tablo-container').innerHTML = `
            <div style="text-align:center;padding:60px;color:#334155;">
                <div style="font-size:48px;margin-bottom:12px;">✅</div>
                <div style="font-size:16px;color:#475569;">Tüm hammaddeler yeterli stok seviyesinde!</div>
            </div>
        `;
        return;
    }
    
    const html = `
        <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                            ${['Öncelik','S/K','Stok Kodu','Hammadde İsmi','Tür','Mevcut Stok','Optimum','Gereken Miktar','Stok Ömrü / Termin','Aylık Ort. Tük.','Tahmini Tükenme','Termin','Sipariş Durumu','İşlem'].map(h => 
                                `<th style="padding:11px 13px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">${h}</th>`
                            ).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${ihtiyacData.map(m => satirOlustur(m)).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    document.getElementById('tablo-container').innerHTML = html;
}

// Tablo satırı oluştur
function satirOlustur(m) {
    const oncelik = m.oran === null 
        ? { renk: '#475569', bg: '#141820', etiket: 'VERİ YOK', icon: '—' }
        : m.oran < 0.5 
            ? { renk: '#ef4444', bg: '#2d1a1a', etiket: 'ACİL SİPARİŞ', icon: '🚨' }
            : m.oran < 1 
                ? { renk: '#f97316', bg: '#2a1a0a', etiket: 'SİPARİŞ VER', icon: '📦' }
                : m.oran < 2 
                    ? { renk: '#eab308', bg: '#1f1e0a', etiket: 'TAKİPTE', icon: '👁' }
                    : { renk: '#34d399', bg: '#0d2018', etiket: 'RAHAT', icon: '✅' };
    
    const yuzde = m.oran !== null ? Math.min(100, Math.round(m.oran * 50)) : 0;
    
    const kalanText = m.kalan_gun !== null 
        ? (m.kalan_gun <= 0 ? 'Tükendi!' : (m.kalan_gun < 30 ? m.kalan_gun + ' gün' : '~' + Math.round(m.kalan_gun / 30) + ' ay'))
        : '—';
    
    const kalanRenk = m.kalan_gun !== null
        ? (m.kalan_gun <= 7 ? '#ef4444' : (m.kalan_gun <= 30 ? '#f97316' : (m.kalan_gun <= 90 ? '#eab308' : '#94a3b8')))
        : '#64748b';
    
    return `
        <tr style="border-bottom:1px solid #1e2430;" onmouseenter="this.style.background='#1a2130'" onmouseleave="this.style.background='transparent'">
            <td style="padding:12px 13px;">
                <span style="background:${oncelik.bg};color:${oncelik.renk};padding:3px 9px;border-radius:5px;font-size:11px;font-weight:700;border:1px solid ${oncelik.renk}44;white-space:nowrap;">
                    ${oncelik.icon} ${oncelik.etiket}
                </span>
            </td>
            <td style="padding:12px 13px;">
                <span style="padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background:${m.sk=='S'?'#1d3557':(m.sk=='A'?'#221a05':'#2d1a1a')};color:${m.sk=='S'?'#60a5fa':(m.sk=='A'?'#fbbf24':'#f87171')};">
                    ${m.sk}
                </span>
            </td>
            <td style="padding:12px 13px;color:#94a3b8;font-size:12px;font-family:monospace;">${m.stok_kodu}</td>
            <td style="padding:12px 13px;font-weight:600;color:#60a5fa;font-size:13px;max-width:220px;cursor:pointer;text-decoration:underline;text-decoration-style:dotted;" 
                onclick="window.location='hammadde-detay.php?id=${m.id}'">${escapeHtml(m.hammadde_ismi)}</td>
            <td style="padding:12px 13px;">
                <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;">${m.tur_adi}</span>
            </td>
            <td style="padding:12px 13px;font-weight:700;color:#f87171;font-size:13px;">${formatNumber(m.stok)}</td>
            <td style="padding:12px 13px;color:#64748b;font-size:12px;">${formatNumber(m.opt)}</td>
            <td style="padding:12px 13px;font-weight:700;color:${oncelik.renk};font-size:13px;">${formatNumber(m.eksik)}</td>
            <td style="padding:12px 13px;min-width:120px;">
                <div style="margin-bottom:4px;display:flex;justify-content:space-between;align-items:center;gap:6px;">
                    <span style="font-size:11px;color:${oncelik.renk};font-weight:700;">%${yuzde}</span>
                    <span style="font-size:9px;color:${oncelik.renk};font-weight:700;opacity:0.8;">
                        ${m.oran>=1?'✓ RAHAT':(m.oran>=0.75?'👁 TAKİP':(m.oran>=0.25?'📦 SİP.VER':'🚨 ACİL'))}
                    </span>
                </div>
                <div style="height:8px;background:#1e2430;border-radius:4px;overflow:hidden;">
                    <div style="width:${yuzde}%;height:100%;border-radius:4px;${(m.oran===null || m.oran<0.5)?'background:linear-gradient(90deg,#ef4444,#f97316)':(m.oran<1?'background:linear-gradient(90deg,#f97316,#eab308)':'background:linear-gradient(90deg,#eab308,#84cc16)')};transition:width 0.5s"></div>
                </div>
            </td>
            <td style="padding:12px 13px;color:#94a3b8;font-size:12px;">${m.aylik_ort > 0 ? formatNumber(Math.round(m.aylik_ort)) : '—'}</td>
            <td style="padding:12px 13px;font-weight:700;color:${kalanRenk};font-size:13px;white-space:nowrap;">${kalanText}</td>
            <td style="padding:12px 13px;color:#64748b;font-size:12px;">${m.termin_gun > 0 ? m.termin_gun + ' gün' : '—'}</td>
            <td style="padding:10px 13px;min-width:220px;">
                ${siparisDurumuHTML(m)}
            </td>
            <td style="padding:12px 13px;">
                <div style="display:flex;gap:6px;">
                    <button onclick="window.location='hammadde-detay.php?id=${m.id}'" title="Detay" style="background:#1e2430;border:none;border-radius:6px;padding:5px 9px;cursor:pointer;font-size:13px;">👁</button>
                    <button onclick="window.location='hammadde-form.php?id=${m.id}'" title="Düzenle" style="background:#1e2430;border:none;border-radius:6px;padding:5px 9px;cursor:pointer;font-size:13px;">✏️</button>
                </div>
            </td>
        </tr>
    `;
}

// Sipariş durumu HTML'i
function siparisDurumuHTML(m) {
    if (m.siparis_verildi && !m.siparis_geldi) {
        return `
            <div style="background:#0d2018;border:1px solid #10b98155;border-radius:8px;padding:8px 10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5;">
                    <span style="font-size:11px;color:#34d399;font-weight:700;">✅ Sipariş Verildi</span>
                    <button onclick="siparisIptal(${m.id})" style="background:#2d1a1a;border:none;border-radius:4px;color:#f87171;font-size:10px;padding:2px 6px;cursor:pointer;">✕ İptal</button>
                </div>
                ${m.siparis_no ? `<div style="font-size:11px;color:#fbbf24;font-weight:700;margin-bottom:3;">📋 ${escapeHtml(m.siparis_no)}</div>` : ''}
                <div style="font-size:11px;color:#94a3b8;margin-bottom:2px;">
                    Miktar: <strong style="color:#34d399;">${formatNumber(m.siparis_miktar)} kg</strong>
                </div>
                ${m.siparis_tarih ? `<div style="font-size:10px;color:#475569;">📅 ${m.siparis_tarih}</div>` : ''}
            </div>
        `;
    }
    
    return `
        <div>
            <div id="siparis-form-${m.id}" style="display:${siparisFormAcik[m.id] ? 'block' : 'none'};background:#0d1a2d;border:1px solid #3b82f655;border-radius:8px;padding:10px;">
                <div style="font-size:11px;color:#60a5fa;font-weight:700;margin-bottom:6px;">🛒 Sipariş Bilgisi</div>
                <div style="margin-bottom:6px;">
                    <div style="font-size:10px;color:#475569;margin-bottom:3px;">Sipariş No</div>
                    <input type="text" id="sip-no-${m.id}" placeholder="ör: PO-2025-001"
                        style="width:100%;background:#141820;border:1px solid #1e2430;border-radius:5px;padding:5px 8px;color:#f1f5f9;font-size:12px;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:8px;">
                    <div style="font-size:10px;color:#475569;margin-bottom:3px;">Miktar (kg)</div>
                    <input type="number" id="sip-miktar-${m.id}" value="${Math.max(0, Math.round((m.opt * 2) - m.stok))}" min="0"
                        style="width:100%;background:#141820;border:1px solid #1e2430;border-radius:5px;padding:5px 8px;color:#34d399;font-weight:700;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="display:flex;gap:6;">
                    <button onclick="siparisKaydet(${m.id})" style="flex:1;background:#10b981;border:none;border-radius:6px;padding:7px;color:#fff;cursor:pointer;font-size:12px;font-weight:700;">
                        ✅ Kaydet
                    </button>
                    <button onclick="siparisFormToggle(${m.id}, false)" style="background:#1e2430;border:none;border-radius:6px;padding:7px 12px;cursor:pointer;color:#64748b;font-size:12px;">İptal</button>
                </div>
            </div>
            <button id="siparis-btn-${m.id}" onclick="siparisFormToggle(${m.id}, true)" style="display:${siparisFormAcik[m.id] ? 'none' : 'block'};background:#1a2535;border:1px solid #3b82f655;border-radius:7px;padding:7px 12px;cursor:pointer;color:#60a5fa;font-size:11px;font-weight:700;width:100%;text-align:center;">
                🛒 Sipariş Verildi İşaretle
            </button>
        </div>
    `;
}

// Sipariş form toggle
function siparisFormToggle(id, acik) {
    siparisFormAcik[id] = acik;
    tabloyuGuncelle();
}

// Sipariş kaydet
function siparisKaydet(id) {
    const sipNo = document.getElementById('sip-no-' + id)?.value || '';
    const miktar = document.getElementById('sip-miktar-' + id)?.value;
    
    if (!miktar || parseFloat(miktar) <= 0) {
        alert('Lütfen geçerli bir miktar girin.');
        return;
    }
    
    fetch('ajax/siparis-ver.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'hammadde_id=' + id + '&miktar=' + miktar + '&siparis_no=' + encodeURIComponent(sipNo)
    })
    .then(r => r.json())
    .then(data => { 
        if (data.success) {
            siparisFormAcik[id] = false;
            verileriYukle(); // Listeyi yenile
            // Header'daki sayacı güncelle
            if (window.updateHeaderBadges) window.updateHeaderBadges();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    })
    .catch(err => {
        console.error('Sipariş kaydetme hatası:', err);
        alert('Bağlantı hatası: ' + err.message);
    });
}

// Sipariş iptal
function siparisIptal(id) {
    if (!confirm('Sipariş iptal edilsin mi?')) return;
    
    fetch('ajax/siparis-guncelle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'hammadde_id=' + id + '&islem=iptal'
    })
    .then(r => r.json())
    .then(data => { 
        if (data.success) {
            verileriYukle(); // Listeyi yenile
            // Header'daki sayacı güncelle
            if (window.updateHeaderBadges) window.updateHeaderBadges();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    })
    .catch(err => {
        console.error('Sipariş iptal hatası:', err);
        alert('Bağlantı hatası: ' + err.message);
    });
}

// Yardımcı fonksiyonlar
function formatNumber(num) {
    return new Intl.NumberFormat('tr-TR').format(Math.round(num));
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Sayfa yüklendiğinde verileri çek
document.addEventListener('DOMContentLoaded', verileriYukle);

// Her 30 saniyede bir otomatik yenileme
setInterval(verileriYukle, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>
