<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? 'owner') === 'admin') {
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit;
}

$owner_id  = $_SESSION['owner_id'];
$objOwner  = new Owner($pdo);
$objRental = new Rental($pdo);
$pesan     = '';

if (isset($_POST['simpan_rental'])) {
    $pesan = $objRental->simpanProfil(
        $owner_id,
        $_POST['nama_rental'],
        $_POST['alamat'],
        $_POST['latitude'],
        $_POST['longitude']
    );
}

$rental = $objRental->getByOwner($owner_id);
if (isset($_POST['simpan_catatan']) && $rental) {
    $pesan  = $objRental->simpanCatatan($rental['rental_id'], $_POST['catatan']);
    $rental = $objRental->getByOwner($owner_id);
}

if (isset($_POST['upload_foto']) && $rental) {
    $pesan = $objRental->uploadFoto($rental['rental_id'], $_FILES['foto'], $_POST['caption']);
}

$daftar_foto = $rental ? $objRental->getFoto($rental['rental_id']) : [];

// polimorphism: seluruh modul turunan BaseModel dipanggil seragam lewat getRingkasan()
$daftarModul = $rental ? [
    $objRental,
    new JadwalBukaTutup($pdo),
    new ConsoleDanHarga($pdo),
    new Game($pdo)
] : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Dashboard Owner — <?= htmlspecialchars($_SESSION['owner_name']); ?></h2>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($rental): ?>
                <a href="jadwal.php">Jadwal</a>
                <a href="konsol.php">Konsol & Harga</a>
                <a href="game.php">Daftar Game</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box"><?= $pesan; ?></div>
        <?php endif; ?>

        <?php if (!$rental): ?>
            <div class="card">
                <h3>Lengkapi Profil & Titik Lokasi Rental</h3>
                <p class="subtitle">Tentukan nama usaha, alamat, dan klik titik lokasi tepat di peta.</p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nama Tempat Rental</label>
                        <input type="text" name="nama_rental" placeholder="Contoh: Victory PS Tondano" required>
                    </div>

                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap tempat rental" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tandai Lokasi di Peta (Klik pada peta)</label>
                        <div id="map"></div>
                        <div class="coord-row">
                            <input type="text" id="latitude" name="latitude" placeholder="Latitude" readonly required>
                            <input type="text" id="longitude" name="longitude" placeholder="Longitude" readonly required>
                        </div>
                    </div>

                    <button type="submit" name="simpan_rental" class="btn">Simpan Profil & Lokasi</button>
                </form>
            </div>

            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                var map = L.map('map').setView([1.3000, 124.8833], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                var marker;
                map.on('click', function(e) {
                    var lat = e.latlng.lat;
                    var lng = e.latlng.lng;
                    if (marker) map.removeLayer(marker);
                    marker = L.marker([lat, lng]).addTo(map);
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                });
            </script>

        <?php else: ?>
            <div class="card">
                <h3>Profil Usaha Anda</h3>
                <ul class="info-list">
                    <li><strong>Nama Rental:</strong> <?= htmlspecialchars($rental['rental_name']); ?></li>
                    <li><strong>Alamat:</strong> <?= htmlspecialchars($rental['address'] ?? '-'); ?></li>
                    <li><strong>Koordinat GPS:</strong> <?= htmlspecialchars($rental['latitude']); ?>, <?= htmlspecialchars($rental['longitude']); ?></li>
                </ul>

                <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                    <div id="map" style="height: 350px; width: 100%; margin-top: 16px; border-radius: 8px;"></div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Ringkasan Status Rental</h3>
                <ul class="info-list">
                    <li>
                        <strong><?= htmlspecialchars($objOwner->getNamaModul()); ?>:</strong>
                        <?= htmlspecialchars($objOwner->getRingkasan($owner_id)); ?>
                    </li>
                    <?php foreach ($daftarModul as $modul): ?>
                        <li>
                            <strong><?= htmlspecialchars($modul->getNamaModul()); ?>:</strong>
                            <?= htmlspecialchars($modul->getRingkasan($rental['rental_id'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($rental['catatan'])): ?>
                    <div style="margin-top: 14px; padding: 12px 14px; background-color: #ffffff; border: 1px solid #878787; border-radius: 8px; color: #1a1a1a;">
                        <strong>Catatan:</strong><br>
                        <?= nl2br(htmlspecialchars($rental['catatan'])); ?>
                    </div>
                <?php endif; ?>

                <details style="margin-top: 12px;">
                    <summary style="cursor: pointer; color: #2563eb; font-weight: 600; font-size: 0.9rem;">
                        <?= !empty($rental['catatan']) ? 'Edit / Hapus Catatan' : '+ Tambah Catatan' ?>
                    </summary>
                    <form method="POST" action="" style="margin-top: 10px;">
                        <div class="form-group">
                            <textarea name="catatan" rows="3" placeholder="Tambahkan catatan..."><?= htmlspecialchars($rental['catatan'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="simpan_catatan" class="btn" style="padding: 8px 16px; font-size: 0.9rem;">Simpan Catatan</button>
                    </form>
                </details>
            </div>

            <div class="card">
                <h3>Menu Manajemen</h3>
                <div class="menu-grid">
                    <a href="jadwal.php" class="menu-card">
                        <strong>Kelola Jadwal Rental</strong>
                        <span>Atur jam operasional Senin–Minggu dan hari libur.</span>
                    </a>
                    <a href="konsol.php" class="menu-card">
                        <strong>Kelola Daftar Konsol & Harga</strong>
                        <span>Tambahkan unit PS 3/4/5 beserta paket tarif per jam.</span>
                    </a>
                    <a href="game.php" class="menu-card">
                        <strong>Kelola Daftar Game</strong>
                        <span>Tambahkan dan atur ketersediaan game untuk tiap konsol.</span>
                    </a>
                </div>
            </div>

            <div class="card">
                <h3>Galeri Foto Tempat Rental</h3>
                <form method="POST" action="" enctype="multipart/form-data" class="form-inline">
                    <div class="form-group">
                        <label>Pilih Foto (Dalam/Luar/Konsol)</label>
                        <input type="file" name="foto" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan (Caption)</label>
                        <input type="text" name="caption" placeholder="Contoh: Tampak Depan Toko" required>
                    </div>
                    <button type="submit" name="upload_foto" class="btn">Unggah Foto</button>
                </form>

                <div class="gallery">
                    <?php if (empty($daftar_foto)): ?>
                        <p class="subtitle">Belum ada foto yang diunggah.</p>
                    <?php else: ?>
                        <?php foreach ($daftar_foto as $f): ?>
                            <div class="gallery-item">
                                <img src="<?= htmlspecialchars($f['photo_url']) ?>" alt="Foto Rental">
                                <p><?= htmlspecialchars($f['caption']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                    var lat = <?= (float)$rental['latitude']; ?>;
                    var lng = <?= (float)$rental['longitude']; ?>;
                    var namaRental = <?= json_encode($rental['rental_name']); ?>;
                    var alamatRental = <?= json_encode($rental['address'] ?? '-'); ?>;

                    var map = L.map('map').setView([lat, lng], 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    L.marker([lat, lng])
                        .addTo(map)
                        .bindPopup('<strong>' + namaRental + '</strong><br>' + alamatRental)
                        .openPopup();
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>