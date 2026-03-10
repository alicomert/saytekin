<?php
// Sabit Tanımlar - Admin yönetim sayfası
require_once 'includes/header.php';

// Sadece admin erişebilir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Sabit Tanımlar';
$db = getDB();

// Aktif tab
$tab = $_GET['tab'] ?? 'turler';

// İşlem kontrolü
$message = '';
$error = '';

// CRUD İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                switch ($table) {
                    case 'hammadde_turleri':
                        $stmt = $db->prepare("INSERT INTO hammadde_turleri (kod, ad, sira, is_active) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['sira'] ?? 0]);
                        break;
                    case 'paketleme_tipleri':
                        $stmt = $db->prepare("INSERT INTO paketleme_tipleri (kod, ad, aciklama) VALUES (?, ?, ?)");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['aciklama']]);
                        break;
                    case 'para_birimleri':
                        $stmt = $db->prepare("INSERT INTO para_birimleri (kod, ad, sembol) VALUES (?, ?, ?)");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['sembol']]);
                        break;
                    case 'teslimat_sekilleri':
                        $stmt = $db->prepare("INSERT INTO teslimat_sekilleri (kod, ad, aciklama, is_active) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['aciklama']]);
                        break;
                    case 'ulkeler':
                        $stmt = $db->prepare("INSERT INTO ulkeler (ad, is_active) VALUES (?, 1)");
                        $stmt->execute([$_POST['ad']]);
                        break;
                }
                $message = 'Kayıt eklendi.';
                break;
                
            case 'edit':
                $id = $_POST['id'];
                switch ($table) {
                    case 'hammadde_turleri':
                        $stmt = $db->prepare("UPDATE hammadde_turleri SET kod=?, ad=?, sira=?, is_active=? WHERE id=?");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['sira'], $_POST['is_active'], $id]);
                        break;
                    case 'paketleme_tipleri':
                        $stmt = $db->prepare("UPDATE paketleme_tipleri SET kod=?, ad=?, aciklama=? WHERE id=?");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['aciklama'], $id]);
                        break;
                    case 'para_birimleri':
                        $stmt = $db->prepare("UPDATE para_birimleri SET kod=?, ad=?, sembol=? WHERE id=?");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['sembol'], $id]);
                        break;
                    case 'teslimat_sekilleri':
                        $stmt = $db->prepare("UPDATE teslimat_sekilleri SET kod=?, ad=?, aciklama=?, is_active=? WHERE id=?");
                        $stmt->execute([$_POST['kod'], $_POST['ad'], $_POST['aciklama'], $_POST['is_active'], $id]);
                        break;
                    case 'ulkeler':
                        $stmt = $db->prepare("UPDATE ulkeler SET ad=?, is_active=? WHERE id=?");
                        $stmt->execute([$_POST['ad'], $_POST['is_active'], $id]);
                        break;
                }
                $message = 'Kayıt güncellendi.';
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $db->query("DELETE FROM {$table} WHERE id = {$id}");
                $message = 'Kayıt silindi.';
                break;
                
            case 'update_user_role':
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$_POST['role'], $_POST['id']]);
                $message = 'Kullanıcı rolü güncellendi.';
                break;
                
            case 'toggle_public_access':
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('public_access', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$_POST['enabled'], $_POST['enabled']]);
                $message = 'Ayar güncellendi.';
                break;
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Verileri çek
$hammaddeTurleri = $db->query("SELECT * FROM hammadde_turleri ORDER BY sira ASC")->fetchAll();
$paketlemeTipleri = $db->query("SELECT * FROM paketleme_tipleri ORDER BY kod ASC")->fetchAll();
$paraBirimleri = $db->query("SELECT * FROM para_birimleri ORDER BY kod ASC")->fetchAll();
$teslimatSekilleri = $db->query("SELECT * FROM teslimat_sekilleri ORDER BY kod ASC")->fetchAll();
$ulkeler = $db->query("SELECT * FROM ulkeler ORDER BY ad ASC")->fetchAll();
$users = $db->query("SELECT id, username, email, full_name, role, is_active, last_login FROM users ORDER BY id ASC")->fetchAll();

// Ayarları çek
$publicAccess = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'public_access'")->fetchColumn() ?? '0';

?>

<style>
.tab-btn {
    padding: 10px 20px;
    border: 1px solid #1e2430;
    background: #141820;
    color: #64748b;
    cursor: pointer;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
}
.tab-btn:hover { border-color: #2d3748; color: #94a3b8; }
.tab-btn.active { border-color: #3b82f6; background: #1d3557; color: #60a5fa; }

.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { 
    background: #0d1017; 
    border-bottom: 2px solid #1e2430; 
    padding: 12px; 
    text-align: left; 
    font-size: 11px; 
    color: #475569; 
    text-transform: uppercase; 
    letter-spacing: 0.05em;
}
.data-table td { 
    padding: 12px; 
    border-bottom: 1px solid #1e2430; 
    font-size: 13px;
}
.data-table tr:hover { background: #1a2130; }

.badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.badge-admin { background: #3b82f633; color: #3b82f6; border: 1px solid #3b82f655; }
.badge-manager { background: #f59e0b33; color: #f59e0b; border: 1px solid #f59e0b55; }
.badge-user { background: #10b98133; color: #10b981; border: 1px solid #10b98155; }
.badge-active { background: #10b98133; color: #10b981; }
.badge-inactive { background: #64748b33; color: #64748b; }

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal.active { display: flex; }
.modal-content {
    background: #141820;
    border: 1px solid #1e2430;
    border-radius: 12px;
    padding: 24px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-bottom: 6px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    background: #0f1117;
    border: 1px solid #1e2430;
    border-radius: 8px;
    padding: 10px 12px;
    color: #e2e8f0;
    font-size: 13px;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    outline: none;
}
.form-group textarea { min-height: 100px; resize: vertical; }

.action-btns { display: flex; gap: 6px; }
.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}
.action-btn-edit { background: #1d3557; color: #60a5fa; }
.action-btn-delete { background: #2d1a1a; color: #ef4444; }

.settings-card {
    background: #141820;
    border: 1px solid #1e2430;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
    background: #1e2430;
    border-radius: 13px;
    cursor: pointer;
    transition: background 0.3s;
}
.toggle-switch.active { background: #10b981; }
.toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.3s;
}
.toggle-switch.active::after { transform: translateX(24px); }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 22px; font-weight: 700; color: #f1f5f9; margin-bottom: 4px;">⚙️ Sabit Tanımlar</h2>
        <p style="color: #475569; font-size: 13px;">Sistem ayarları ve sabit değerlerin yönetimi</p>
    </div>
    
    <?php if ($message): ?>
    <div style="background: #10b98133; border: 1px solid #10b98155; color: #10b981; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
        ✓ <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div style="background: #ef444433; border: 1px solid #ef444455; color: #ef4444; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
        ✕ <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <!-- Sekmeler -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?tab=turler" class="tab-btn <?php echo $tab === 'turler' ? 'active' : ''; ?>">📦 Hammadde Türleri</a>
        <a href="?tab=paketleme" class="tab-btn <?php echo $tab === 'paketleme' ? 'active' : ''; ?>">📋 Paketleme Tipleri</a>
        <a href="?tab=parabirimi" class="tab-btn <?php echo $tab === 'parabirimi' ? 'active' : ''; ?>">💱 Para Birimleri</a>
        <a href="?tab=teslimat" class="tab-btn <?php echo $tab === 'teslimat' ? 'active' : ''; ?>">🚚 Teslimat Şekilleri</a>
        <a href="?tab=ulkeler" class="tab-btn <?php echo $tab === 'ulkeler' ? 'active' : ''; ?>">🌍 Ülkeler</a>
        <a href="?tab=users" class="tab-btn <?php echo $tab === 'users' ? 'active' : ''; ?>">👥 Kullanıcılar</a>
        <a href="?tab=ayarlar" class="tab-btn <?php echo $tab === 'ayarlar' ? 'active' : ''; ?>">⚙️ Ayarlar</a>
    </div>

    <!-- Hammadde Türleri -->
    <?php if ($tab === 'turler'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">📦 Hammadde Türleri</h3>
            <button onclick="openModal('turler')" class="btn-primary">+ Yeni Tür Ekle</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hammaddeTurleri as $item): ?>
                    <tr>
                        <td><?php echo $item['sira']; ?></td>
                        <td><code style="background: #1e2430; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($item['kod']); ?></code></td>
                        <td><?php echo htmlspecialchars($item['ad']); ?></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button onclick='editTur(<?php echo json_encode($item); ?>)' class="action-btn action-btn-edit">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="hammadde_turleri">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Paketleme Tipleri -->
    <?php if ($tab === 'paketleme'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">📋 Paketleme Tipleri</h3>
            <button onclick="openModal('paketleme')" class="btn-primary">+ Yeni Paketleme Ekle</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Açıklama</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paketlemeTipleri as $item): ?>
                    <tr>
                        <td><code style="background: #1e2430; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($item['kod']); ?></code></td>
                        <td><?php echo htmlspecialchars($item['ad']); ?></td>
                        <td><?php echo htmlspecialchars($item['aciklama']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='editPaketleme(<?php echo json_encode($item); ?>)' class="action-btn action-btn-edit">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="paketleme_tipleri">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Para Birimleri -->
    <?php if ($tab === 'parabirimi'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">💱 Para Birimleri</h3>
            <button onclick="openModal('parabirimi')" class="btn-primary">+ Yeni Para Birimi Ekle</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Sembol</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paraBirimleri as $item): ?>
                    <tr>
                        <td><code style="background: #1e2430; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?php echo htmlspecialchars($item['kod']); ?></code></td>
                        <td><?php echo htmlspecialchars($item['ad']); ?></td>
                        <td style="font-size: 16px;"><?php echo $item['sembol']; ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='editParaBirimi(<?php echo json_encode($item); ?>)' class="action-btn action-btn-edit">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="para_birimleri">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Teslimat Şekilleri -->
    <?php if ($tab === 'teslimat'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">🚚 Teslimat Şekilleri</h3>
            <button onclick="openModal('teslimat')" class="btn-primary">+ Yeni Teslimat Şekli Ekle</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Açıklama</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teslimatSekilleri as $item): ?>
                    <tr>
                        <td><code style="background: #1e2430; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?php echo htmlspecialchars($item['kod']); ?></code></td>
                        <td><?php echo htmlspecialchars($item['ad']); ?></td>
                        <td><?php echo htmlspecialchars(substr($item['aciklama'], 0, 100)) . '...'; ?></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button onclick='editTeslimat(<?php echo json_encode($item); ?>)' class="action-btn action-btn-edit">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="teslimat_sekilleri">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ülkeler -->
    <?php if ($tab === 'ulkeler'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">🌍 Ülkeler</h3>
            <button onclick="openModal('ulke')" class="btn-primary">+ Yeni Ülke Ekle</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ulkeler as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['ad']); ?></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button onclick='editUlke(<?php echo json_encode($item); ?>)' class="action-btn action-btn-edit">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="ulkeler">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kullanıcılar -->
    <?php if ($tab === 'users'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title" style="margin: 0;">👥 Kullanıcılar ve Rol Yönetimi</h3>
            <a href="kullanici-ekle.php" class="btn-primary">+ Yeni Kullanıcı Ekle</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kullanıcı Adı</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                        <td><?php echo htmlspecialchars($item['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_user_role">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <select name="role" onchange="this.form.submit()" style="background: #0f1117; border: 1px solid #1e2430; border-radius: 6px; padding: 6px 10px; color: #e2e8f0; font-size: 12px; cursor: pointer;">
                                    <option value="admin" <?php echo $item['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo $item['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="user" <?php echo $item['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 12px;">
                            <?php echo $item['last_login'] ? date('d.m.Y H:i', strtotime($item['last_login'])) : 'Hiç giriş yapmadı'; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="kullanici-duzenle.php?id=<?php echo $item['id']; ?>" class="action-btn action-btn-edit" style="text-decoration: none; display: inline-flex; align-items: center;">Düzenle</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 24px; padding: 16px; background: #0f1117; border-radius: 8px;">
            <h4 style="color: #f1f5f9; font-size: 14px; margin-bottom: 12px;">🔐 Rol Açıklamaları</h4>
            <div style="display: grid; gap: 10px; font-size: 13px;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge badge-admin">Admin</span>
                    <span style="color: #94a3b8;">Tam yetki - Tüm işlemleri yapabilir, kullanıcıları yönetebilir</span>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge badge-manager">Manager</span>
                    <span style="color: #94a3b8;">Kısıtlı yetki - Ekleme/güncelleme yapabilir, silemez</span>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge badge-user">User</span>
                    <span style="color: #94a3b8;">Sadece görüntüleme - Verileri sadece okuyabilir</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ayarlar -->
    <?php if ($tab === 'ayarlar'): ?>
    <div class="settings-card">
        <h3 class="section-title" style="margin-top: 0;">🔓 Genel Erişim Ayarları</h3>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #0f1117; border-radius: 8px;">
            <div>
                <div style="font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Giriş Yapmadan Erişime İzin Ver</div>
                <div style="font-size: 12px; color: #64748b;">Aktif edilirse, kullanıcılar giriş yapmadan verileri görüntüleyebilir (salt okunur)</div>
            </div>
            <form method="POST" id="publicAccessForm">
                <input type="hidden" name="action" value="toggle_public_access">
                <input type="hidden" name="enabled" id="publicAccessValue" value="<?php echo $publicAccess; ?>">
                <div class="toggle-switch <?php echo $publicAccess === '1' ? 'active' : ''; ?>" onclick="togglePublicAccess()" id="publicAccessToggle"></div>
            </form>
        </div>
        
        <div style="margin-top: 16px; padding: 12px; background: <?php echo $publicAccess === '1' ? '#f59e0b22' : '#1e2430'; ?>; border: 1px solid <?php echo $publicAccess === '1' ? '#f59e0b55' : '#1e2430'; ?>; border-radius: 8px;">
            <div style="font-size: 12px; color: <?php echo $publicAccess === '1' ? '#fbbf24' : '#64748b'; ?>">
                <?php if ($publicAccess === '1'): ?>
                ⚠️ <strong>Uyarı:</strong> Sistem şu anda herkese açık. Verileri kimse düzenleyemez ama herkes görüntüleyebilir.
                <?php else: ?>
                ℹ️ Sistem şu anda sadece giriş yapmış kullanıcılara açık.
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modals -->
<div id="modal-turler" class="modal">
    <div class="modal-content">
        <h3 style="color: #f1f5f9; margin-bottom: 20px;">📦 Hammadde Türü Ekle/Düzenle</h3>
        <form method="POST" id="form-turler">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="table" value="hammadde_turleri">
            <input type="hidden" name="id" id="tur-id">
            <div class="form-group">
                <label>Kod</label>
                <input type="text" name="kod" id="tur-kod" required maxlength="20">
            </div>
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="ad" id="tur-ad" required maxlength="50">
            </div>
            <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sira" id="tur-sira" value="0">
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="is_active" id="tur-active">
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn-secondary">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-paketleme" class="modal">
    <div class="modal-content">
        <h3 style="color: #f1f5f9; margin-bottom: 20px;">📋 Paketleme Tipi Ekle/Düzenle</h3>
        <form method="POST" id="form-paketleme">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="table" value="paketleme_tipleri">
            <input type="hidden" name="id" id="paketleme-id">
            <div class="form-group">
                <label>Kod</label>
                <input type="text" name="kod" id="paketleme-kod" required maxlength="20">
            </div>
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="ad" id="paketleme-ad" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="aciklama" id="paketleme-aciklama" required></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn-secondary">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-parabirimi" class="modal">
    <div class="modal-content">
        <h3 style="color: #f1f5f9; margin-bottom: 20px;">💱 Para Birimi Ekle/Düzenle</h3>
        <form method="POST" id="form-parabirimi">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="table" value="para_birimleri">
            <input type="hidden" name="id" id="parabirimi-id">
            <div class="form-group">
                <label>Kod (3 harf)</label>
                <input type="text" name="kod" id="parabirimi-kod" required maxlength="3" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="ad" id="parabirimi-ad" required maxlength="50">
            </div>
            <div class="form-group">
                <label>Sembol</label>
                <input type="text" name="sembol" id="parabirimi-sembol" required maxlength="5">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn-secondary">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-teslimat" class="modal">
    <div class="modal-content">
        <h3 style="color: #f1f5f9; margin-bottom: 20px;">🚚 Teslimat Şekli Ekle/Düzenle</h3>
        <form method="POST" id="form-teslimat">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="table" value="teslimat_sekilleri">
            <input type="hidden" name="id" id="teslimat-id">
            <div class="form-group">
                <label>Kod (3 harf)</label>
                <input type="text" name="kod" id="teslimat-kod" required maxlength="3" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Ad</label>
                <input type="text" name="ad" id="teslimat-ad" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="aciklama" id="teslimat-aciklama" required></textarea>
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="is_active" id="teslimat-active">
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn-secondary">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-ulke" class="modal">
    <div class="modal-content">
        <h3 style="color: #f1f5f9; margin-bottom: 20px;">🌍 Ülke Ekle/Düzenle</h3>
        <form method="POST" id="form-ulke">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="table" value="ulkeler">
            <input type="hidden" name="id" id="ulke-id">
            <div class="form-group">
                <label>Ülke Adı</label>
                <input type="text" name="ad" id="ulke-ad" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="is_active" id="ulke-active">
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn-secondary">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(type) {
    document.getElementById('modal-' + type).classList.add('active');
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
    // Reset forms
    document.querySelectorAll('form[id^="form-"]').forEach(f => f.reset());
    document.querySelectorAll('input[id$="-id"]').forEach(i => i.value = '');
    document.querySelectorAll('select[name="action"]').forEach(s => s.value = 'add');
}

function editTur(data) {
    document.getElementById('tur-id').value = data.id;
    document.getElementById('tur-kod').value = data.kod;
    document.getElementById('tur-ad').value = data.ad;
    document.getElementById('tur-sira').value = data.sira;
    document.getElementById('tur-active').value = data.is_active;
    document.querySelector('#form-turler input[name="action"]').value = 'edit';
    openModal('turler');
}

function editPaketleme(data) {
    document.getElementById('paketleme-id').value = data.id;
    document.getElementById('paketleme-kod').value = data.kod;
    document.getElementById('paketleme-ad').value = data.ad;
    document.getElementById('paketleme-aciklama').value = data.aciklama;
    document.querySelector('#form-paketleme input[name="action"]').value = 'edit';
    openModal('paketleme');
}

function editParaBirimi(data) {
    document.getElementById('parabirimi-id').value = data.id;
    document.getElementById('parabirimi-kod').value = data.kod;
    document.getElementById('parabirimi-ad').value = data.ad;
    document.getElementById('parabirimi-sembol').value = data.sembol;
    document.querySelector('#form-parabirimi input[name="action"]').value = 'edit';
    openModal('parabirimi');
}

function editTeslimat(data) {
    document.getElementById('teslimat-id').value = data.id;
    document.getElementById('teslimat-kod').value = data.kod;
    document.getElementById('teslimat-ad').value = data.ad;
    document.getElementById('teslimat-aciklama').value = data.aciklama;
    document.getElementById('teslimat-active').value = data.is_active;
    document.querySelector('#form-teslimat input[name="action"]').value = 'edit';
    openModal('teslimat');
}

function editUlke(data) {
    document.getElementById('ulke-id').value = data.id;
    document.getElementById('ulke-ad').value = data.ad;
    document.getElementById('ulke-active').value = data.is_active;
    document.querySelector('#form-ulke input[name="action"]').value = 'edit';
    openModal('ulke');
}

function togglePublicAccess() {
    const toggle = document.getElementById('publicAccessToggle');
    const valueInput = document.getElementById('publicAccessValue');
    const isActive = toggle.classList.contains('active');
    
    if (isActive) {
        toggle.classList.remove('active');
        valueInput.value = '0';
    } else {
        toggle.classList.add('active');
        valueInput.value = '1';
    }
    
    document.getElementById('publicAccessForm').submit();
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
