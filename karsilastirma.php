<?php
require_once 'includes/header.php';

$pageTitle = 'Fiyat Karsilastirma';

$db = getDB();
$hammaddeler = $db->query("SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi,
                            ts.ad as teslimat_adi, pb.sembol as para_sembol
                            FROM hammaddeler h
                            LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
                            LEFT JOIN ulkeler u ON h.mensei_ulke_id = u.id
                            LEFT JOIN paketleme_tipleri pt ON h.paketleme_kodu = pt.kod
                            LEFT JOIN teslimat_sekilleri ts ON h.teslimat_sekli_kodu = ts.kod
                            LEFT JOIN para_birimleri pb ON h.para_birimi_kodu = pb.kod
                            WHERE h.is_active = 1 AND h.birim_fiyat > 0
                            ORDER BY h.hammadde_ismi")
                       ->fetchAll();

$kurlar = getDovizKurlari();
?>

<div style="margin-bottom:20px;">
    <div style="font-size:18px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">⚖️ Fiyat Karsilastirma</div>
    <div style="font-size:13px;color:#64748b;">Karsilastirmak istediginiz hammaddeleri secin (en az 2)</div>
</div>

<!-- Secim Paneli -->
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:20px;margin-bottom:20px;">
    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;">
        Hammadde Secimi
        <span id="secim_sayisi" style="margin-left:8px;background:#1e2430;padding:2px 8px;border-radius:4px;color:#f1f5f9;">0 secili</span>
        <button onclick="secimiTemizle()" style="margin-left:12px;background:transparent;border:none;color:#ef4444;font-size:11px;cursor:pointer;">
            Temizle
        </button>
    </div>
    
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach ($hammaddeler as $h): 
            $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
        ?>
        <button type="button" onclick="toggleSecim(<?php echo $h['id']; ?>, this)" 
                data-id="<?php echo $h['id']; ?>"
                class="secim-btn" 
                style="padding:10px 16px;background:#0f1117;border:1px solid #1e2430;border-radius:8px;text-align:left;cursor:pointer;transition:all 0.15s;">
            <div style="font-weight:700;color:#94a3b8;font-size:13px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?php echo $h['hammadde_ismi']; ?>
            </div>
            <div style="font-size:11px;color:#34d399;margin-top:4px;">
                <?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $h['para_birimi_kodu']; ?>/<?php echo $h['fiyat_birimi']; ?>
            </div>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Karsilastirma Tablosu -->
<div id="karsilastirma_tablosu" style="display:none;">
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid #1e2430;">
            <span style="font-weight:700;color:#f1f5f9;"><span id="karsilastirma_sayisi">0</span> hammadde karsilastiriliyor</span>
        </div>
        
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#0f1117;">
                        <th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid #1e2430;">KRITER</th>
                    </tr>
                </thead>
                <tbody id="karsilastirma_body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bos durum -->
<div id="bos_durum" style="text-align:center;padding:48px 0;">
    <div style="font-size:48;margin-bottom:16px;">⚖️</div>
    <div style="color:#64748b;">Yukaridan en az 2 hammadde secerek karsilastirma yapabilirsiniz.</div>
</div>

<script>
const hammaddeler = <?php echo json_encode($hammaddeler); ?>;
const kurlar = <?php echo json_encode($kurlar); ?>;
let seciliIds = [];

function toggleSecim(id, btn) {
    const index = seciliIds.indexOf(id);
    
    if (index === -1) {
        seciliIds.push(id);
        btn.style.borderColor = '#3b82f6';
        btn.style.background = '#1d3557';
    } else {
        seciliIds.splice(index, 1);
        btn.style.borderColor = '#1e2430';
        btn.style.background = '#0f1117';
    }
    
    document.getElementById('secim_sayisi').textContent = seciliIds.length + ' secili';
    guncelleKarsilastirma();
}

function secimiTemizle() {
    seciliIds = [];
    document.querySelectorAll('.secim-btn').forEach(btn => {
        btn.style.borderColor = '#1e2430';
        btn.style.background = '#0f1117';
    });
    document.getElementById('secim_sayisi').textContent = '0 secili';
    guncelleKarsilastirma();
}

function hesaplaVaris(h) {
    const birimFiyat = parseFloat(h.birim_fiyat) || 0;
    const maliyetDeger = parseFloat(h.maliyet_deger) || 0;
    let maliyet = 0;
    
    if (h.maliyet_tipi === 'yuzde') {
        maliyet = birimFiyat * (maliyetDeger / 100);
    } else {
        maliyet = maliyetDeger;
    }
    
    return birimFiyat + maliyet;
}

function guncelleKarsilastirma() {
    const tablo = document.getElementById('karsilastirma_tablosu');
    const bosDurum = document.getElementById('bos_durum');
    const thead = tablo.querySelector('thead tr');
    const tbody = document.getElementById('karsilastirma_body');
    
    if (seciliIds.length < 2) {
        tablo.style.display = 'none';
        bosDurum.style.display = 'block';
        return;
    }
    
    tablo.style.display = 'block';
    bosDurum.style.display = 'none';
    document.getElementById('karsilastirma_sayisi').textContent = seciliIds.length;
    
    const seciliHammaddeler = hammaddeler.filter(h => seciliIds.includes(parseInt(h.id)));
    
    // Header
    thead.innerHTML = '<th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid #1e2430;">KRITER</th>';
    seciliHammaddeler.forEach(h => {
        thead.innerHTML += `
            <th style="text-align:left;color:#60a5fa;font-weight:700;font-size:13px;padding:12px 16px;border-bottom:1px solid #1e2430;min-width:160px;">
                <div style="color:#f1f5f9;margin-bottom:4px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${h.hammadde_ismi}">${h.hammadde_ismi}</div>
                <div style="font-size:11px;color:#64748b;font-weight:400;">${h.stok_kodu || '-'}</div>
            </th>
        `;
    });
    
    // Kriterler
    const kriterler = [
        { label: 'Tedarikci', key: 'tedarikci', default: '-' },
        { label: 'Mensei Ulke', key: 'ulke_adi', default: '-' },
        { label: 'Paketleme', key: 'paketleme_adi', default: '-' },
        { label: 'Teslim Sekli', key: 'teslimat_sekli_kodu', default: '-' },
        { label: 'Para Birimi', key: 'para_birimi_kodu', default: '-' },
        { label: 'Fiyat Birimi', key: 'fiyat_birimi', prefix: '/' },
        { label: 'Maliyet Turu', key: 'maliyet_turu', transform: v => v === 'G' ? 'G - Gerceklesen' : 'T - Tahmini' },
        { label: 'Birim Fiyat', key: 'birim_fiyat', numeric: true, suffix: h => ' ' + h.para_birimi_kodu, format: v => parseFloat(v).toFixed(2) },
        { label: 'Birim Varis Maliyeti', key: null, calculate: h => hesaplaVaris(h), numeric: true, vurgulu: true, suffix: h => ' ' + h.para_birimi_kodu + '/' + h.fiyat_birimi, format: v => v.toFixed(2) },
        { label: 'Mevcut Stok', key: 'stok_miktari', suffix: () => ' kg', format: v => parseFloat(v || 0).toLocaleString('tr-TR', {maximumFractionDigits: 0}) }
    ];
    
    // Body
    tbody.innerHTML = '';
    kriterler.forEach((kriter, idx) => {
        let row = `<tr style="border-bottom:1px solid #1e2430;${idx % 2 === 1 ? 'background:#0f1117;' : ''}">
            <td style="padding:12px 16px;color:#64748b;font-weight:700;font-size:11px;text-transform:uppercase;">${kriter.label}</td>`;
        
        seciliHammaddeler.forEach(h => {
            let value;
            if (kriter.calculate) {
                value = kriter.calculate(h);
            } else if (kriter.key) {
                value = h[kriter.key] || kriter.default || '-';
                if (kriter.transform) value = kriter.transform(value);
            }
            
            if (kriter.format && value !== '-') {
                value = kriter.format(value);
            }
            
            if (kriter.suffix && value !== '-') {
                value += kriter.suffix(h);
            }
            
            if (kriter.prefix && value !== '-') {
                value = kriter.prefix + value;
            }
            
            const color = kriter.vurguli ? '#34d399' : (kriter.numeric ? '#60a5fa' : '#94a3b8');
            const weight = (kriter.vurguli || kriter.numeric) ? '700' : '400';
            
            row += `<td style="padding:12px 16px;color:${color};font-weight:${weight};">${value}</td>`;
        });
        
        row += '</tr>';
        tbody.innerHTML += row;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>