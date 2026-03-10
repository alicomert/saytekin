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

<div class="animate-fade-in">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">⚖️ Fiyat Karsilastirma</h1>
        <p class="text-gray-500 text-sm mt-1">Karsilastirmak istediginiz hammaddeleri secin (en az 2)</p>
    </div>
    
    <!-- Secim Paneli -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 mb-6">
        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">
            Hammadde Secimi
            <span id="secim_sayisi" class="ml-2 text-white bg-dark-700 px-2 py-0.5 rounded">0 secili</span>
            <button onclick="secimiTemizle()" class="ml-4 text-red-400 hover:text-red-300 text-xs font-normal">
                <i class="fas fa-times mr-1"></i>Temizle
            </button>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <?php foreach ($hammaddeler as $h): 
                $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
            ?>
            <button type="button" onclick="toggleSecim(<?php echo $h['id']; ?>, this)" 
                    data-id="<?php echo $h['id']; ?>"
                    class="secim-btn px-4 py-3 bg-dark-900 border border-dark-700 hover:border-blue-500/50 rounded-lg text-left transition-all group">
                <div class="font-bold text-gray-300 group-hover:text-white text-sm truncate max-w-[200px]">
                    <?php echo $h['hammadde_ismi']; ?>
                </div>
                <div class="text-xs text-green-400 mt-1">
                    <?php echo formatNumber($varisMaliyet, 2); ?> <?php echo $h['para_birimi_kodu']; ?>/<?php echo $h['fiyat_birimi']; ?>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Karsilastirma Tablosu -->
    <div id="karsilastirma_tablosu" class="hidden">
        <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-dark-700">
                <span class="font-bold text-white"><span id="karsilastirma_sayisi">0</span> hammadde karsilastiriliyor</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-dark-900">
                            <th class="text-left text-gray-500 font-bold text-xs uppercase tracking-wider p-4 border-b border-dark-700">KRITER</th>
                            <!-- Dinamik olarak hammadde kolonlari eklenecek -->
                        </tr>
                    </thead>
                    <tbody id="karsilastirma_body">
                        <!-- Dinamik olarak satirlar eklenecek -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Bos durum -->
    <div id="bos_durum" class="text-center py-12">
        <div class="text-5xl mb-4">⚖️</div>
        <div class="text-gray-500">Yukaridan en az 2 hammadde secerek karsilastirma yapabilirsiniz.</div>
    </div>
</div>

<script>
const hammaddeler = <?php echo json_encode($hammaddeler); ?>;
const kurlar = <?php echo json_encode($kurlar); ?>;
let seciliIds = [];

function toggleSecim(id, btn) {
    const index = seciliIds.indexOf(id);
    
    if (index === -1) {
        seciliIds.push(id);
        btn.classList.add('border-blue-500', 'bg-blue-500/10');
        btn.classList.remove('border-dark-700', 'bg-dark-900');
    } else {
        seciliIds.splice(index, 1);
        btn.classList.remove('border-blue-500', 'bg-blue-500/10');
        btn.classList.add('border-dark-700', 'bg-dark-900');
    }
    
    document.getElementById('secim_sayisi').textContent = seciliIds.length + ' secili';
    guncelleKarsilastirma();
}

function secimiTemizle() {
    seciliIds = [];
    document.querySelectorAll('.secim-btn').forEach(btn => {
        btn.classList.remove('border-blue-500', 'bg-blue-500/10');
        btn.classList.add('border-dark-700', 'bg-dark-900');
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
        tablo.classList.add('hidden');
        bosDurum.classList.remove('hidden');
        return;
    }
    
    tablo.classList.remove('hidden');
    bosDurum.classList.add('hidden');
    document.getElementById('karsilastirma_sayisi').textContent = seciliIds.length;
    
    // Secili hammaddeleri bul
    const seciliHammaddeler = hammaddeler.filter(h => seciliIds.includes(parseInt(h.id)));
    
    // Header'i guncelle
    thead.innerHTML = '<th class="text-left text-gray-500 font-bold text-xs uppercase tracking-wider p-4 border-b border-dark-700">KRITER</th>';
    seciliHammaddeler.forEach(h => {
        thead.innerHTML += `
            <th class="text-left text-blue-400 font-bold text-sm p-4 border-b border-dark-700 min-w-[160px]">
                <div class="text-white mb-1 truncate max-w-[150px]" title="${h.hammadde_ismi}">${h.hammadde_ismi}</div>
                <div class="text-xs text-gray-500 font-normal">${h.stok_kodu || '-'}</div>
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
        { 
            label: 'Birim Fiyat', 
            key: 'birim_fiyat', 
            numeric: true,
            suffix: h => ' ' + h.para_birimi_kodu,
            format: v => parseFloat(v).toFixed(2)
        },
        { 
            label: 'Birim Varis Maliyeti', 
            key: null,
            calculate: h => hesaplaVaris(h),
            numeric: true,
            vurgulu: true,
            suffix: h => ' ' + h.para_birimi_kodu + '/' + h.fiyat_birimi,
            format: v => v.toFixed(2)
        },
        { 
            label: 'Mevcut Stok', 
            key: 'stok_miktari', 
            suffix: () => ' kg',
            format: v => parseFloat(v || 0).toLocaleString('tr-TR', {maximumFractionDigits: 0})
        }
    ];
    
    // Body'i guncelle
    tbody.innerHTML = '';
    kriterler.forEach((kriter, idx) => {
        let row = `<tr class="border-b border-dark-700 ${idx % 2 === 0 ? '' : 'bg-dark-900/50'}">
            <td class="p-4 text-gray-500 font-bold text-xs uppercase">${kriter.label}</td>`;
        
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
            
            const colorClass = kriter.vurguli ? 'text-green-400 font-bold text-lg' : (kriter.numeric ? 'text-blue-400 font-bold' : 'text-gray-300');
            
            row += `<td class="p-4 ${colorClass}">${value}</td>`;
        });
        
        row += '</tr>';
        tbody.innerHTML += row;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
