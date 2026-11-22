<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

$rental_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$objRental  = new Rental($pdo);
$objConsole = new ConsoleDanHarga($pdo);
$objGame    = new Game($pdo);
$objJadwal  = new JadwalBukaTutup($pdo);

$rental = $rental_id > 0 ? $objRental->getDetailById($rental_id) : false;

if (!$rental) {
    header("Location: index.php");
    exit;
}

$daftar_foto   = $objRental->getFoto($rental_id);
$daftar_konsol = $objConsole->getDaftarConsole($rental_id);
$daftar_harga  = $objConsole->getDaftarHarga($rental_id);
$daftar_game   = $objGame->getDaftarGame($rental_id);
$jadwal        = $objJadwal->getJadwal($rental_id);
$hari_list     = $objJadwal->getHariList();

// Kelompokkan daftar game berdasarkan nama konsol (PS 3, PS 4, PS 5)
$game_per_konsol = [];
foreach ($daftar_game as $g) {
    $game_per_konsol[$g['console_name']][] = $g;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($rental['rental_name']) ?> — Detail Rental</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body {
            background-color: #0b2239;
        }
        #map {
            height: 300px;
            width: 100%;
            border-radius: 10px;
            z-index: 1;
        }
        .gallery-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Direktori Rental PS</a>
            <div class="ms-auto d-flex gap-2">
                <a href="index.php" class="btn btn-outline-light btn-sm">&laquo; Kembali ke Daftar</a>
                <?php if (isset($_SESSION['owner_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">Dashboard Owner</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm">Login Owner</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container my-4">

        <!-- Header Profil Rental -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($rental['rental_name']) ?></h2>
                        <p class="text-muted mb-2"><?= htmlspecialchars($rental['address'] ?? '-') ?></p>
                        <div class="small text-secondary">
                            Pemilik: <strong><?= htmlspecialchars($rental['owner_name']) ?></strong>
                            <?php if (!empty($rental['phone'])): ?>
                                &bull; Telp/WA: <strong><?= htmlspecialchars($rental['phone']) ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                            <a
                                href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($rental['latitude'] . ',' . $rental['longitude']) ?>"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-primary"
                            >
                                Rute Google Maps
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($rental['catatan'])): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Catatan:</strong> <?= nl2br(htmlspecialchars($rental['catatan'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kolom Kiri: Unit Konsol, Paket Harga, Daftar Game, Galeri -->
            <div class="col-lg-8">

                <!-- Unit Konsol Tersedia -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Unit Konsol yang Tersedia</h5>
                        <?php if (empty($daftar_konsol)): ?>
                            <p class="text-muted mb-0">Belum ada unit konsol yang terdaftar.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($daftar_konsol as $k): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-dark fs-6"><?= htmlspecialchars($k['console_name']) ?></span>
                                                <span class="badge <?= ($k['status'] === 'tersedia' || $k['available_units'] > 0) ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ucfirst(htmlspecialchars($k['status'] ?? 'Tersedia')) ?>
                                                </span>
                                            </div>
                                            <div class="mt-2 small text-muted">
                                                Total Unit: <strong class="text-dark"><?= (int)$k['total_units'] ?> unit</strong><br>
                                                Unit Tersedia: <strong class="text-success"><?= (int)$k['available_units'] ?> unit</strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Paket Harga Sewa -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Daftar Harga Sewa</h5>
                        <?php if (empty($daftar_harga)): ?>
                            <p class="text-muted mb-0">Belum ada paket harga yang diatur.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Konsol</th>
                                            <th>Paket</th>
                                            <th>Durasi</th>
                                            <th>Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_harga as $h): ?>
                                            <tr>
                                                <td><span class="badge bg-dark"><?= htmlspecialchars($h['console_name']) ?></span></td>
                                                <td><?= htmlspecialchars($h['package_name']) ?></td>
                                                <td><?= (int)$h['duration_hours'] ?> Jam</td>
                                                <td class="fw-semibold text-success">
                                                    Rp <?= number_format($h['price'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Daftar Game per Konsol -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Daftar Game</h5>
                        <?php if (empty($game_per_konsol)): ?>
                            <p class="text-muted mb-0">Belum ada daftar game yang ditambahkan.</p>
                        <?php else: ?>
                            <?php foreach ($game_per_konsol as $nama_konsol => $games): ?>
                                <div class="mb-3">
                                    <h6 class="fw-bold mb-2">
                                        <span class="badge bg-primary me-1"><?= htmlspecialchars($nama_konsol) ?></span>
                                        <span class="text-muted small fw-normal">(<?= count($games) ?> game)</span>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Judul Game</th>
                                                    <th>Genre</th>
                                                    <th style="width: 140px;">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($games as $g): ?>
                                                    <tr>
                                                        <td class="fw-medium"><?= htmlspecialchars($g['game_name']) ?></td>
                                                        <td class="text-muted"><?= htmlspecialchars($g['genre'] ?? '-') ?></td>
                                                        <td>
                                                            <?php if ($g['status'] === 'tersedia'): ?>
                                                                <span class="badge bg-success">Tersedia</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Tidak Tersedia</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Galeri Foto -->
                <?php if (!empty($daftar_foto)): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Galeri Foto</h5>
                            <div class="row g-3">
                                <?php foreach ($daftar_foto as $f): ?>
                                    <?php if (file_exists($f['photo_url'])): ?>
                                        <div class="col-sm-6 col-md-4">
                                            <img src="<?= htmlspecialchars($f['photo_url']) ?>" class="gallery-img mb-1" alt="<?= htmlspecialchars($f['caption']) ?>">
                                            <div class="small text-muted"><?= htmlspecialchars($f['caption']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Kolom Kanan: Peta Lokasi & Jadwal Operasional -->
            <div class="col-lg-4">
                <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Lokasi Peta</h5>
                            <div id="map"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Jadwal Operasional</h5>
                        <?php if (empty($jadwal)): ?>
                            <p class="text-muted mb-0">Jadwal belum diatur oleh pemilik.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($hari_list as $hari): ?>
                                    <?php $j = $jadwal[$hari] ?? null; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span><?= htmlspecialchars($hari) ?></span>
                                        <?php if (!$j): ?>
                                            <span class="text-muted small">-</span>
                                        <?php elseif ($j['is_closed'] == 1): ?>
                                            <span class="badge bg-danger">Libur</span>
                                        <?php else: ?>
                                            <span class="fw-semibold small">
                                                <?= substr($j['opening_time'], 0, 5) ?> – <?= substr($j['closing_time'], 0, 5) ?>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            var lat = <?= (float)$rental['latitude'] ?>;
            var lng = <?= (float)$rental['longitude'] ?>;
            var map = L.map('map').setView([lat, lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([lat, lng])
                .addTo(map)
                .bindPopup('<strong>' + <?= json_encode($rental['rental_name']) ?> + '</strong>')
                .openPopup();
        </script>
    <?php endif; ?>
</body>
</html>