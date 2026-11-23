<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

$rental_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$objRental  = new Rental($pdo);
$objConsole = new ConsoleDanHarga($pdo);
$objGame    = new Game($pdo);
$objJadwal  = new JadwalBukaTutup($pdo);
$objRating  = new rating_tempat($pdo);

$rental = $rental_id > 0 ? $objRental->getDetailById($rental_id) : false;

if (!$rental) {
    header("Location: index.php");
    exit;
}

$pesan_rating = '';
if (isset($_POST['kirim_rating'])) {
    try {
        $objRating->tambahRating(
            $rental_id,
            $_POST['reviewer_name'] ?? '',
            $_POST['rating'] ?? 0,
            $_POST['review_text'] ?? ''
        );
        header("Location: detail.php?id=" . $rental_id . "&rated=1#ulasan");
        exit;
    } catch (Exception $e) {
        $pesan_rating = $e->getMessage();
    }
}

$rating_info   = $objRating->getRating($rental_id);
$avg_rating    = $rating_info && $rating_info['total_reviews'] > 0 ? round((float)$rating_info['avg_rating'], 1) : null;
$total_reviews = (int)($rating_info['total_reviews'] ?? 0);
$daftar_ulasan = $objRating->getDaftarUlasan($rental_id);

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

// Cari harga terendah per jam untuk ringkasan di header
$harga_terendah = null;
foreach ($daftar_harga as $h) {
    $durasi  = max(1, (int)$h['duration_hours']);
    $per_jam = (float)$h['price'] / $durasi;
    if ($harga_terendah === null || $per_jam < $harga_terendah) {
        $harga_terendah = $per_jam;
    }
}

$dashboard_url = (($_SESSION['role'] ?? '') === 'admin') ? 'admin.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($rental['rental_name'] ?? 'Detail Rental') ?> — RentalPS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --ps-purple: #6c63ff;
            --ps-purple-hover: #574fe0;
            --ps-purple-soft: rgba(108, 99, 255, 0.1);
            --ps-bg: #f4f5fb;
            --ps-text: #1f2430;
            --ps-muted: #6c7280;
        }

        body.detail-page {
            background-color: var(--ps-bg);
            color: var(--ps-text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .detail-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e7e9f5;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .detail-brand {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--ps-text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .detail-brand span {
            color: var(--ps-purple);
        }

        .btn-purple {
            background-color: var(--ps-purple);
            color: #ffffff;
            border: 1.5px solid var(--ps-purple);
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 18px;
            transition: background-color 0.2s;
        }

        .btn-purple:hover {
            background-color: var(--ps-purple-hover);
            border-color: var(--ps-purple-hover);
            color: #ffffff;
        }

        .btn-outline-purple {
            background-color: #ffffff;
            color: var(--ps-purple);
            border: 1.5px solid var(--ps-purple);
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 16px;
            transition: all 0.2s;
        }

        .btn-outline-purple:hover {
            background-color: var(--ps-purple-soft);
            color: var(--ps-purple-hover);
        }

        .detail-card {
            background: #ffffff;
            border: 1px solid #eceef8;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(31, 36, 48, 0.04);
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ps-text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .section-title i {
            color: var(--ps-purple);
        }

        .console-box {
            background: #f8f9fe;
            border: 1.5px solid #e6e8f6;
            border-radius: 12px;
            padding: 14px;
            height: 100%;
            transition: border-color 0.2s;
        }

        .console-box:hover {
            border-color: var(--ps-purple);
        }

        .badge-purple {
            background-color: var(--ps-purple);
            color: #ffffff;
            font-weight: 600;
        }

        .badge-purple-soft {
            background-color: var(--ps-purple-soft);
            color: var(--ps-purple);
            font-weight: 600;
        }

        .note-callout {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: 12px 16px;
            color: #92400e;
            font-size: 0.92rem;
        }

        .table-clean thead th {
            background-color: #f8f9fe;
            color: var(--ps-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #e7e9f5;
            padding: 10px 12px;
        }

        .table-clean tbody td {
            padding: 11px 12px;
            border-color: #f0f2fa;
        }

        .gallery-card {
            cursor: pointer;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #eceef8;
            background: #f8f9fe;
        }

        .gallery-img {
            height: 185px;
            width: 100%;
            object-fit: cover;
            transition: transform 0.25s ease;
        }

        .gallery-card:hover .gallery-img {
            transform: scale(1.04);
        }

        #map {
            height: 290px;
            width: 100%;
            border-radius: 12px;
            z-index: 1;
        }

        .sticky-sidebar {
            position: sticky;
            top: 84px;
        }
    </style>
</head>
<body class="detail-page">

    <!-- NAVBAR -->
    <nav class="detail-navbar">
        <div class="container d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <a class="detail-brand" href="index.php">
                <i class="bi bi-controller fs-3" style="color:#6c63ff;"></i>
                Rental<span>PS</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-purple btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <?php if (isset($_SESSION['owner_id'])): ?>
                    <a href="<?= $dashboard_url ?>" class="btn btn-purple btn-sm">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-purple btn-sm">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container my-4">

        <!-- HEADER PROFIL RENTAL -->
        <section class="detail-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <h1 class="h3 fw-bold mb-0"><?= htmlspecialchars($rental['rental_name'] ?? '') ?></h1>
                        <?php if (!empty($rental['status_toko'])): ?>
                            <?php
                                $st = strtolower($rental['status_toko']);
                                $badgeClass = $st === 'buka' ? 'bg-success-subtle text-success border border-success-subtle'
                                            : ($st === 'libur' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
                                            : 'bg-danger-subtle text-danger border border-danger-subtle');
                            ?>
                            <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2">
                                <?= ucfirst(htmlspecialchars($rental['status_toko'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted mb-2 d-flex align-items-start gap-2">
                        <i class="bi bi-geo-alt-fill mt-1" style="color:#6c63ff;"></i>
                        <span><?= htmlspecialchars($rental['address'] ?? 'Alamat belum diisi') ?></span>
                    </p>

                    <div class="d-flex flex-wrap gap-3 small text-secondary">
                        <span>
                            <i class="bi bi-person-circle me-1"></i>
                            Pemilik: <strong class="text-dark"><?= htmlspecialchars($rental['owner_name'] ?? '-') ?></strong>
                        </span>
                        <?php if (!empty($rental['phone'])): ?>
                            <span>
                                <i class="bi bi-telephone-fill me-1"></i>
                                Telp/WA: <strong class="text-dark"><?= htmlspecialchars($rental['phone']) ?></strong>
                            </span>
                        <?php endif; ?>
                        <?php if ($harga_terendah !== null): ?>
                            <span>
                                <i class="bi bi-tag-fill me-1" style="color:#6c63ff;"></i>
                                Mulai <strong style="color:#6c63ff;">Rp<?= number_format($harga_terendah, 0, ',', '.') ?>/jam</strong>
                            </span>
                        <?php endif; ?>
                        <span>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <?php if ($avg_rating !== null): ?>
                                <strong class="text-dark"><?= number_format($avg_rating, 1) ?></strong>
                                (<?= $total_reviews ?> ulasan)
                            <?php else: ?>
                                <span>Belum ada rating</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                        <a
                            href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($rental['latitude'] . ',' . $rental['longitude']) ?>"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-purple"
                        >
                            <i class="bi bi-sign-turn-right-fill me-1"></i> Rute Google Maps
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($rental['catatan'])): ?>
                <div class="note-callout mt-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Catatan:</strong> <?= nl2br(htmlspecialchars($rental['catatan'])) ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="row g-4">
            <!-- KOLOM KIRI -->
            <div class="col-lg-8">

                <!-- UNIT KONSOL TERSEDIA -->
                <section class="detail-card p-4 mb-4">
                    <h2 class="section-title">
                        <i class="bi bi-controller"></i> Unit Konsol yang Tersedia
                    </h2>
                    <?php if (empty($daftar_konsol)): ?>
                        <p class="text-muted mb-0">Belum ada unit konsol yang terdaftar.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($daftar_konsol as $k): ?>
                                <?php $isReady = ($k['status'] === 'tersedia' && (int)$k['available_units'] > 0); ?>
                                <div class="col-sm-6 col-md-4">
                                    <div class="console-box">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge badge-purple px-3 py-2 rounded-pill">
                                                <?= htmlspecialchars($k['console_name'] ?? '') ?>
                                            </span>
                                            <span class="badge rounded-pill <?= $isReady ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                                <?= ucfirst(htmlspecialchars($k['status'] ?? 'Tersedia')) ?>
                                            </span>
                                        </div>
                                        <div class="mt-3 small text-muted">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Total Unit</span>
                                                <strong class="text-dark"><?= (int)$k['total_units'] ?> unit</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Tersedia</span>
                                                <strong class="<?= $isReady ? 'text-success' : 'text-secondary' ?>">
                                                    <?= (int)$k['available_units'] ?> unit
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- DAFTAR HARGA SEWA -->
                <section class="detail-card p-4 mb-4">
                    <h2 class="section-title">
                        <i class="bi bi-cash-coin"></i> Daftar Harga Sewa
                    </h2>
                    <?php if (empty($daftar_harga)): ?>
                        <p class="text-muted mb-0">Belum ada paket harga yang diatur.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-clean table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Konsol</th>
                                        <th>Paket</th>
                                        <th>Durasi</th>
                                        <th class="text-end">Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftar_harga as $h): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-purple-soft px-2 py-1 rounded-pill">
                                                    <?= htmlspecialchars($h['console_name'] ?? '') ?>
                                                </span>
                                            </td>
                                            <td class="fw-medium"><?= htmlspecialchars($h['package_name'] ?? '') ?></td>
                                            <td class="text-muted"><?= (int)$h['duration_hours'] ?> Jam</td>
                                            <td class="text-end fw-bold" style="color:#6c63ff;">
                                                Rp<?= number_format((float)$h['price'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- DAFTAR GAME PER KONSOL -->
                <section class="detail-card p-4 mb-4">
                    <h2 class="section-title">
                        <i class="bi bi-disc-fill"></i> Daftar Game
                    </h2>
                    <?php if (empty($game_per_konsol)): ?>
                        <p class="text-muted mb-0">Belum ada daftar game yang ditambahkan.</p>
                    <?php else: ?>
                        <?php foreach ($game_per_konsol as $nama_konsol => $games): ?>
                            <div class="mb-4 last-child-mb-0">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge badge-purple px-3 py-1 rounded-pill">
                                        <?= htmlspecialchars($nama_konsol) ?>
                                    </span>
                                    <span class="text-muted small">(<?= count($games) ?> game)</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-clean table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Judul Game</th>
                                                <th>Genre</th>
                                                <th style="width: 140px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($games as $g): ?>
                                                <tr>
                                                    <td class="fw-medium"><?= htmlspecialchars($g['game_name'] ?? '') ?></td>
                                                    <td class="text-muted"><?= htmlspecialchars($g['genre'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if (($g['status'] ?? '') === 'tersedia'): ?>
                                                            <span class="badge rounded-pill bg-success-subtle text-success px-3">Tersedia</span>
                                                        <?php else: ?>
                                                            <span class="badge rounded-pill bg-danger-subtle text-danger px-3">Tidak Tersedia</span>
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
                </section>

                <!-- GALERI FOTO -->
                <?php if (!empty($daftar_foto)): ?>
                    <section class="detail-card p-4 mb-4">
                        <h2 class="section-title">
                            <i class="bi bi-images"></i> Galeri Foto
                        </h2>
                        <div class="row g-3">
                            <?php foreach ($daftar_foto as $f): ?>
                                <?php if (!empty($f['photo_url']) && file_exists($f['photo_url'])): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div
                                            class="gallery-card"
                                            data-bs-toggle="modal"
                                            data-bs-target="#photoModal"
                                            data-src="<?= htmlspecialchars($f['photo_url']) ?>"
                                            data-caption="<?= htmlspecialchars($f['caption'] ?? '') ?>"
                                        >
                                            <img
                                                src="<?= htmlspecialchars($f['photo_url']) ?>"
                                                class="gallery-img"
                                                alt="<?= htmlspecialchars($f['caption'] ?? $rental['rental_name']) ?>"
                                            >
                                            <?php if (!empty($f['caption'])): ?>
                                                <div class="px-2 py-2 small text-muted text-truncate">
                                                    <?= htmlspecialchars($f['caption']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- RATING & ULASAN PENGUNJUNG -->
                <section class="detail-card p-4 mb-4" id="ulasan">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h2 class="section-title mb-0">
                            <i class="bi bi-star-fill text-warning"></i> Rating & Ulasan
                        </h2>
                        <?php if ($avg_rating !== null): ?>
                            <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle px-3 py-2">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                <?= number_format($avg_rating, 1) ?> / 5 (<?= $total_reviews ?> ulasan)
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_GET['rated'])): ?>
                        <div class="alert alert-success py-2 small rounded-3">
                            Terima kasih! Ulasan dan rating kamu berhasil ditambahkan.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($pesan_rating)): ?>
                        <div class="alert alert-danger py-2 small rounded-3">
                            <?= htmlspecialchars($pesan_rating) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="detail.php?id=<?= $rental_id ?>#ulasan" class="p-3 rounded-3 mb-4" style="background:#f8f9fe; border:1px solid #e6e8f6;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nama Kamu</label>
                                <input type="text" name="reviewer_name" class="form-control form-control-sm" placeholder="Contoh: Andi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Beri Rating</label>
                                <select name="rating" class="form-select form-select-sm" required>
                                    <option value="5">★★★★★ (5 - Sangat Bagus)</option>
                                    <option value="4">★★★★☆ (4 - Bagus)</option>
                                    <option value="3">★★★☆☆ (3 - Cukup)</option>
                                    <option value="2">★★☆☆☆ (2 - Kurang)</option>
                                    <option value="1">★☆☆☆☆ (1 - Buruk)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Ulasan <span class="text-muted fw-normal">(Opsional)</span></label>
                                <textarea name="review_text" rows="2" class="form-control form-control-sm" placeholder="Bagaimana kenyamanan tempat, stik, atau gamenya?"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="kirim_rating" class="btn btn-purple btn-sm">
                                    <i class="bi bi-send-fill me-1"></i> Kirim Ulasan
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if (empty($daftar_ulasan)): ?>
                        <p class="text-muted small mb-0">Belum ada ulasan. Jadilah yang pertama memberi rating tempat ini!</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($daftar_ulasan as $u): ?>
                                <div class="pb-3 border-bottom border-light-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="small"><?= htmlspecialchars($u['reviewer_name']) ?></strong>
                                        <span class="text-warning small">
                                            <?= str_repeat('★', (int)$u['rating']) . str_repeat('☆', 5 - (int)$u['rating']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($u['review_text'])): ?>
                                        <p class="small text-secondary mb-1"><?= nl2br(htmlspecialchars($u['review_text'])) ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted" style="font-size:0.75rem;">
                                        <?= date('d M Y, H:i', strtotime($u['created_at'])) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- KOLOM KANAN: PETA & JADWAL -->
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
                        <section class="detail-card p-4 mb-4">
                            <h2 class="section-title">
                                <i class="bi bi-map-fill"></i> Lokasi Peta
                            </h2>
                            <div id="map" class="mb-3"></div>
                            <a
                                href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($rental['latitude'] . ',' . $rental['longitude']) ?>"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-outline-purple w-100 btn-sm"
                            >
                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Google Maps
                            </a>
                        </section>
                    <?php endif; ?>

                    <section class="detail-card p-4">
                        <h2 class="section-title">
                            <i class="bi bi-clock-fill"></i> Jadwal Operasional
                        </h2>
                        <?php if (empty($jadwal)): ?>
                            <p class="text-muted mb-0">Jadwal belum diatur oleh pemilik.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($hari_list as $hari): ?>
                                    <?php $j = $jadwal[$hari] ?? null; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-light-subtle">
                                        <span class="fw-medium"><?= htmlspecialchars($hari) ?></span>
                                        <?php if (!$j): ?>
                                            <span class="text-muted small">-</span>
                                        <?php elseif ($j['is_closed'] == 1): ?>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger px-3">Libur</span>
                                        <?php else: ?>
                                            <span class="fw-semibold small" style="color:#6c63ff;">
                                                <?= substr($j['opening_time'], 0, 5) ?> – <?= substr($j['closing_time'], 0, 5) ?>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL PERBESAR FOTO -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold" id="photoModalCaption">Foto Rental</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center pt-2">
                    <img id="photoModalImg" src="" alt="Preview" class="img-fluid rounded-3" style="max-height:75vh;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Script modal galeri foto
        const photoModal = document.getElementById('photoModal');
        if (photoModal) {
            photoModal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                if (!trigger) return;
                document.getElementById('photoModalImg').src = trigger.getAttribute('data-src') || '';
                document.getElementById('photoModalCaption').textContent = trigger.getAttribute('data-caption') || 'Galeri Foto';
            });
        }
    </script>

    <?php if (!empty($rental['latitude']) && !empty($rental['longitude'])): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const lat = <?= (float)$rental['latitude'] ?>;
            const lng = <?= (float)$rental['longitude'] ?>;
            const map = L.map('map').setView([lat, lng], 16);

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