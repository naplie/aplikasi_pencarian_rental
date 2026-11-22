<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

$objRental = new Rental($pdo);

$keyword    = $_GET['q'] ?? '';
$console_id = $_GET['console_id'] ?? '';

$daftar_konsol = $objRental->getSemuaJenisKonsol();
$hasil_rental  = $objRental->cariRental($keyword, $console_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direktori Rental PlayStation</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body {
            background-color: #0f172a;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
        }
        #map {
            height: 380px;
            width: 100%;
            border-radius: 12px;
            z-index: 1;
        }
        .rental-thumb {
            height: 190px;
            object-fit: cover;
        }
        .thumb-placeholder {
            height: 190px;
            background-color: #334155;
            color: #94a3b8;
        }
        .rental-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .rental-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1.2rem rgba(0, 0, 0, 0.25) !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Direktori Rental PS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <div class="ms-auto d-flex gap-2 mt-2 mt-lg-0">
                    <?php if (isset($_SESSION['owner_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-sm">Dashboard Owner</a>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm">Login Owner</a>
                        <a href="register.php" class="btn btn-primary btn-sm">Daftarkan Rental</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h1 class="fw-bold mb-2">Temukan Rental PlayStation Terdekat</h1>
                <p class="text-light opacity-75 mb-0">
                    Cari berdasarkan nama tempat, alamat, judul game favorit, atau jenis konsol.
                </p>
            </div>

            <form method="GET" action="index.php" class="card border-0 shadow p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <input
                            type="text"
                            name="q"
                            class="form-control form-control-lg"
                            placeholder="Cari nama rental, alamat, atau game (misal: GTA V, FC 25)..."
                            value="<?= htmlspecialchars($keyword) ?>"
                        >
                    </div>
                    <div class="col-md-3">
                        <select name="console_id" class="form-select form-select-lg">
                            <option value="">Semua Konsol</option>
                            <?php foreach ($daftar_konsol as $k): ?>
                                <option value="<?= $k['console_id'] ?>" <?= ((string)$console_id === (string)$k['console_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['console_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Cari</button>
                        <?php if ($keyword !== '' || $console_id !== ''): ?>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <main class="container my-4">
        <!-- Peta Persebaran Rental -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold mb-0">Peta Lokasi Rental</h5>
                    <span class="badge bg-primary rounded-pill">
                        <?= count($hasil_rental) ?> tempat ditemukan
                    </span>
                </div>
                <div id="map"></div>
            </div>
        </div>

        <!-- Daftar Hasil Pencarian -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-white">
                <?php if ($keyword !== '' || $console_id !== ''): ?>
                    Hasil Pencarian
                <?php else: ?>
                    Daftar Rental Tersedia
                <?php endif; ?>
            </h4>
        </div>

        <?php if (empty($hasil_rental)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <h5 class="fw-bold text-secondary">Rental tidak ditemukan</h5>
                    <p class="text-muted mb-3">Coba ubah kata kunci pencarian atau pilih "Semua Konsol".</p>
                    <a href="index.php" class="btn btn-primary">Tampilkan Semua Rental</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($hasil_rental as $r): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card rental-card h-100 border-0 shadow-sm overflow-hidden">
                            <?php if (!empty($r['thumbnail']) && file_exists($r['thumbnail'])): ?>
                                <img src="<?= htmlspecialchars($r['thumbnail']) ?>" class="card-img-top rental-thumb" alt="<?= htmlspecialchars($r['rental_name']) ?>">
                            <?php else: ?>
                                <div class="thumb-placeholder d-flex align-items-center justify-content-center fw-semibold">
                                    Belum ada foto
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-1">
                                    <a href="detail.php?id=<?= (int)$r['rental_id'] ?>" class="text-decoration-none text-dark stretched-link">
                                        <?= htmlspecialchars($r['rental_name']) ?>
                                    </a>
                                </h5>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($r['address'] ?? '-') ?></p>

                                <!-- Badge Konsol -->
                                <div class="mb-2">
                                    <?php if (!empty($r['daftar_konsol'])): ?>
                                        <?php foreach (explode(', ', $r['daftar_konsol']) as $kn): ?>
                                            <span class="badge bg-dark me-1"><?= htmlspecialchars($kn) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Konsol belum diatur</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Harga Terendah -->
                                <p class="fw-semibold text-success mb-2">
                                    <?php if ($r['harga_terendah'] !== null): ?>
                                        Mulai Rp <?= number_format($r['harga_terendah'], 0, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="text-muted fw-normal small">Tarif belum tersedia</span>
                                    <?php endif; ?>
                                </p>

                                <!-- Catatan Owner (jika ada) -->
                                <?php if (!empty($r['catatan'])): ?>
                                    <div class="alert alert-warning py-1 px-2 small mb-3">
                                        <?= nl2br(htmlspecialchars($r['catatan'])) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Tombol Aksi -->
                                <div class="mt-auto d-flex gap-2 position-relative z-2">
                                    <?php if (!empty($r['latitude']) && !empty($r['longitude'])): ?>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm w-100"
                                            onclick="fokusKePeta(<?= (int)$r['rental_id'] ?>)"
                                        >
                                            Lihat di Peta
                                        </button>
                                        <a
                                            href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($r['latitude'] . ',' . $r['longitude']) ?>"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-primary btn-sm w-100"
                                        >
                                            Rute Maps
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white border-top py-3 mt-5 text-center text-muted small">
        <div class="container">Direktori Pencarian Rental PlayStation</div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        var rentals = <?= json_encode($hasil_rental, JSON_UNESCAPED_UNICODE) ?>;
        var map = L.map('map').setView([1.3000, 124.8833], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var markersById = {};
        var bounds = [];

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        rentals.forEach(function(item) {
            var lat = parseFloat(item.latitude);
            var lng = parseFloat(item.longitude);

            if (!isNaN(lat) && !isNaN(lng)) {
                var harga = item.harga_terendah
                    ? 'Mulai Rp ' + Number(item.harga_terendah).toLocaleString('id-ID')
                    : 'Tarif belum diatur';

                var popupHtml =
                    '<strong>' + escapeHtml(item.rental_name) + '</strong><br>' +
                    '<span style="font-size:0.85rem;">' + escapeHtml(item.address) + '</span><br>' +
                    '<span style="color:#16a34a;font-weight:600;">' + harga + '</span><br>' +
                    '<a href="detail.php?id=' + item.rental_id + '" style="display:inline-block;margin-top:6px;">Lihat Detail &raquo;</a>';

                var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupHtml);
                markersById[item.rental_id] = marker;
                bounds.push([lat, lng]);
            }
        });

        // Otomatis sesuaikan zoom peta agar semua pin hasil pencarian terlihat
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
        }

        function fokusKePeta(rentalId) {
            var marker = markersById[rentalId];
            if (marker) {
                document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
                map.setView(marker.getLatLng(), 16);
                marker.openPopup();
            }
        }
    </script>
</body>
</html>