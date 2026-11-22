<?php
session_start();
require_once 'koneksi.php';

$q        = trim($_GET['q'] ?? '');
$konsol   = trim($_GET['konsol'] ?? '');
$tersedia = ($_GET['tersedia'] ?? '') === '1';

// Ambil data rental beserta foto utama, harga terendah/jam, daftar konsol, dan lokasi
$sql = "
SELECT
    r.rental_id,
    r.rental_name,
    r.status_toko,
    l.address,
    l.latitude,
    l.longitude,
    (
        SELECT rp.photo_url
        FROM rental_photos rp
        WHERE rp.rental_id = r.rental_id
        ORDER BY rp.is_primary DESC, rp.photo_id ASC
        LIMIT 1
    ) AS photo_url,
    (
        SELECT MIN(rr.price / GREATEST(rr.duration_hours, 1))
        FROM rental_rates rr
        INNER JOIN rental_consoles rc2 ON rc2.rental_console_id = rr.rental_console_id
        WHERE rc2.rental_id = r.rental_id
    ) AS harga_per_jam,
    (
        SELECT GROUP_CONCAT(DISTINCT c.console_name ORDER BY c.console_id SEPARATOR ', ')
        FROM rental_consoles rc3
        INNER JOIN consoles c ON c.console_id = rc3.console_id
        WHERE rc3.rental_id = r.rental_id
    ) AS daftar_konsol,
    (
        SELECT COALESCE(SUM(rc4.available_units), 0)
        FROM rental_consoles rc4
        WHERE rc4.rental_id = r.rental_id AND rc4.status = 'tersedia'
    ) AS unit_tersedia
FROM rentals r
LEFT JOIN locations l ON l.rental_id = r.rental_id
WHERE 1=1
";

$params = [];

if ($q !== '') {
    $sql .= " AND (
        r.rental_name LIKE :q1
        OR l.address LIKE :q2
        OR EXISTS (
            SELECT 1 FROM rental_consoles rcg
            INNER JOIN rental_games rg ON rg.rental_console_id = rcg.rental_console_id
            INNER JOIN games g ON g.game_id = rg.game_id
            WHERE rcg.rental_id = r.rental_id AND g.game_name LIKE :q3
        )
        OR EXISTS (
            SELECT 1 FROM rental_consoles rcc
            INNER JOIN consoles c ON c.console_id = rcc.console_id
            WHERE rcc.rental_id = r.rental_id AND c.console_name LIKE :q4
        )
    )";
    $like = '%' . $q . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
}

if ($konsol !== '') {
    $sql .= " AND EXISTS (
        SELECT 1 FROM rental_consoles rcf
        INNER JOIN consoles cf ON cf.console_id = rcf.console_id
        WHERE rcf.rental_id = r.rental_id AND cf.console_name = :konsol
    )";
    $params[':konsol'] = $konsol;
}

if ($tersedia) {
    $sql .= " AND r.status_toko = 'buka' AND EXISTS (
        SELECT 1 FROM rental_consoles rca
        WHERE rca.rental_id = r.rental_id
          AND rca.status = 'tersedia'
          AND rca.available_units > 0
    )";
}

$sql .= " ORDER BY r.rental_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper membangun URL filter agar pencarian dan chip tetap sinkron
function buildFilterUrl(array $override = []): string {
    $query = [
        'q'        => $_GET['q'] ?? '',
        'konsol'   => $_GET['konsol'] ?? '',
        'tersedia' => $_GET['tersedia'] ?? '',
    ];
    foreach ($override as $k => $v) {
        $query[$k] = $v;
    }
    $query = array_filter($query, fn($val) => $val !== '' && $val !== null);
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RentalPS — Direktori Rental PlayStation Terdekat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="home-page">

  <!-- HEADER & SEARCH BAR -->
  <header class="home-header">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <a href="index.php" class="home-brand">
        <i class="bi bi-controller fs-3 text-primary" style="color:#6c63ff !important;"></i>
        Rental<span>PS</span>
      </a>

      <form action="index.php" method="GET" class="home-search-wrap">
        <?php if ($konsol !== ''): ?>
          <input type="hidden" name="konsol" value="<?= htmlspecialchars($konsol) ?>">
        <?php endif; ?>
        <?php if ($tersedia): ?>
          <input type="hidden" name="tersedia" value="1">
        <?php endif; ?>
        <i class="bi bi-search search-icon"></i>
        <input
          type="text"
          name="q"
          class="home-search-input"
          placeholder="Rental PS Terdekat, alamat, atau nama game..."
          value="<?= htmlspecialchars($q) ?>"
        >
      </form>

      <div>
        <?php if (!empty($_SESSION['owner_id'])): ?>
          <a href="dashboard.php" class="btn-masuk">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
        <?php else: ?>
          <a href="login.php" class="btn-masuk">Masuk</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- FILTER CHIPS -->
    <div class="home-filter-bar">
      <a href="<?= buildFilterUrl(['konsol' => ($konsol === 'PS 5' ? '' : 'PS 5')]) ?>"
         class="filter-chip <?= $konsol === 'PS 5' ? 'active' : '' ?>">
        PS 5
      </a>
      <a href="<?= buildFilterUrl(['konsol' => ($konsol === 'PS 4' ? '' : 'PS 4')]) ?>"
         class="filter-chip <?= $konsol === 'PS 4' ? 'active' : '' ?>">
        PS 4
      </a>
      <a href="<?= buildFilterUrl(['konsol' => ($konsol === 'PS 3' ? '' : 'PS 3')]) ?>"
         class="filter-chip <?= $konsol === 'PS 3' ? 'active' : '' ?>">
        PS 3
      </a>
      <a href="<?= buildFilterUrl(['tersedia' => ($tersedia ? '' : '1')]) ?>"
         class="filter-chip <?= $tersedia ? 'active' : '' ?>">
        <i class="bi bi-check-circle"></i> Tersedia
      </a>
      <button type="button" id="btnTerdekat" class="filter-chip">
        <i class="bi bi-geo-alt"></i> Terdekat
      </button>
      <?php if ($q !== '' || $konsol !== '' || $tersedia): ?>
        <a href="index.php" class="filter-chip text-danger border-danger-subtle">
          <i class="bi bi-x-lg"></i> Reset
        </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- KONTEN UTAMA SPLIT-SCREEN -->
  <main class="split-wrapper">
    <!-- KOLOM KIRI: DAFTAR KARTU RENTAL -->
    <section class="rental-sidebar" id="rentalList">
      <?php if (empty($rentals)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-controller fs-1 d-block mb-2"></i>
          <p class="mb-1 fw-semibold">Belum ada tempat rental yang cocok.</p>
          <small>Coba ganti kata kunci atau matikan sebagian filter.</small>
        </div>
      <?php else: ?>
        <?php foreach ($rentals as $r):
          $id      = (int) $r['rental_id'];
          $lat     = $r['latitude'] !== null ? (float) $r['latitude'] : null;
          $lng     = $r['longitude'] !== null ? (float) $r['longitude'] : null;
          $foto    = !empty($r['photo_url']) && file_exists($r['photo_url'])
                     ? $r['photo_url']
                     : 'https://placehold.co/300x300/6c63ff/ffffff?text=Rental+PS';
          $harga   = $r['harga_per_jam'] !== null
                     ? 'Rp' . number_format((float)$r['harga_per_jam'], 0, ',', '.') . '/jam'
                     : 'Harga belum diatur';
          $status  = ucfirst($r['status_toko'] ?? 'buka');
          $ruteUrl = ($lat !== null && $lng !== null)
                     ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}"
                     : '#';
        ?>
          <article
            class="rental-card-item"
            data-id="<?= $id ?>"
            data-lat="<?= $lat ?? '' ?>"
            data-lng="<?= $lng ?? '' ?>"
          >
            <div class="rental-thumb-wrap">
              <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($r['rental_name']) ?>">
              <span class="thumb-badge"><?= htmlspecialchars($status) ?></span>
            </div>

            <div class="rental-info">
              <div>
                <h2 class="rental-title"><?= htmlspecialchars($r['rental_name']) ?></h2>
                <div class="rental-meta">
                  <span class="distance-label fw-semibold text-dark">
                    <i class="bi bi-geo-alt-fill text-primary" style="color:#6c63ff !important;"></i>
                    <span class="distance-text">Lihat peta</span>
                  </span>
                  <?php if (!empty($r['daftar_konsol'])): ?>
                    <span>&bull;</span>
                    <span><?= htmlspecialchars($r['daftar_konsol']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="rental-price"><?= htmlspecialchars($harga) ?></div>
                <div class="rental-sub">
                  <?= htmlspecialchars($r['address'] ?: 'Alamat belum diisi') ?>
                  <?= (int)$r['unit_tersedia'] > 0 ? ' &bull; ' . (int)$r['unit_tersedia'] . ' unit tersedia' : '' ?>
                </div>
              </div>

              <div class="rental-actions">
                <?php if ($lat !== null && $lng !== null): ?>
                  <a href="<?= htmlspecialchars($ruteUrl) ?>"
                     target="_blank"
                     rel="noopener"
                     class="btn-rute"
                     onclick="event.stopPropagation();">
                    RUTE
                  </a>
                <?php endif; ?>
                <a href="detail.php?id=<?= $id ?>"
                   class="btn-detail"
                   onclick="event.stopPropagation();">
                  Detail
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- KOLOM KANAN: PETA INTERAKTIF LEAFLET -->
    <section class="map-panel">
      <div id="map"></div>
    </section>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const rentalsData = <?= json_encode($rentals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    // Inisialisasi peta (default koordinat Tondano jika data kosong)
    const map = L.map('map').setView([1.2789, 124.8772], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markersById = {};
    const bounds = [];

    rentalsData.forEach((item) => {
      const lat = parseFloat(item.latitude);
      const lng = parseFloat(item.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      const hargaText = item.harga_per_jam
        ? 'Rp' + Number(item.harga_per_jam).toLocaleString('id-ID') + '/jam'
        : 'Harga belum diatur';

      const popupHtml = `
        <div style="min-width:180px;">
          <strong style="font-size:0.95rem;">${escapeHtml(item.rental_name)}</strong><br>
          <span style="color:#6c63ff;font-weight:700;">${hargaText}</span><br>
          <small style="color:#6c7280;">${escapeHtml(item.address || '')}</small>
          <div style="margin-top:8px;display:flex;gap:6px;">
            <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank"
               style="background:#6c63ff;color:#fff;padding:4px 10px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;">RUTE</a>
            <a href="detail.php?id=${item.rental_id}"
               style="border:1px solid #6c63ff;color:#6c63ff;padding:4px 10px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;">Detail</a>
          </div>
        </div>
      `;

      const marker = L.marker([lat, lng]).addTo(map).bindPopup(popupHtml);
      markersById[item.rental_id] = marker;
      bounds.push([lat, lng]);

      marker.on('click', () => highlightCard(item.rental_id, true));
    });

    if (bounds.length > 0) {
      map.fitBounds(bounds, { padding: [45, 45], maxZoom: 15 });
    }

    // Klik kartu di kiri -> fokus ke pin di peta
    document.querySelectorAll('.rental-card-item').forEach((card) => {
      card.addEventListener('click', () => {
        const id  = card.dataset.id;
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        highlightCard(id, false);

        if (!isNaN(lat) && !isNaN(lng) && markersById[id]) {
          map.flyTo([lat, lng], 16, { duration: 0.7 });
          markersById[id].openPopup();
        }
      });
    });

    function highlightCard(id, shouldScroll) {
      document.querySelectorAll('.rental-card-item').forEach((el) => {
        el.classList.toggle('active', el.dataset.id === String(id));
      });
      if (shouldScroll) {
        const target = document.querySelector(`.rental-card-item[data-id="${id}"]`);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    // Rumus Haversine (Km) untuk tombol "Terdekat"
    function hitungJarakKm(lat1, lon1, lat2, lon2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
      return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    let userMarker = null;
    const btnTerdekat = document.getElementById('btnTerdekat');

    btnTerdekat.addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert('Browser tidak mendukung deteksi lokasi.');
        return;
      }

      btnTerdekat.classList.add('active');
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const uLat = pos.coords.latitude;
          const uLng = pos.coords.longitude;

          if (userMarker) map.removeLayer(userMarker);
          userMarker = L.circleMarker([uLat, uLng], {
            radius: 9,
            fillColor: '#2563eb',
            color: '#ffffff',
            weight: 3,
            fillOpacity: 1
          }).addTo(map).bindPopup('<strong>Lokasi Anda</strong>').openPopup();

          map.flyTo([uLat, uLng], 14, { duration: 0.8 });

          // Hitung jarak setiap kartu lalu urutkan dari yang terdekat
          const listEl = document.getElementById('rentalList');
          const cards = Array.from(listEl.querySelectorAll('.rental-card-item'));

          cards.forEach((card) => {
            const lat = parseFloat(card.dataset.lat);
            const lng = parseFloat(card.dataset.lng);
            const distText = card.querySelector('.distance-text');

            if (!isNaN(lat) && !isNaN(lng)) {
              const km = hitungJarakKm(uLat, uLng, lat, lng);
              card.dataset.distance = km;
              distText.textContent = km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(1)} Km`;
            } else {
              card.dataset.distance = 999999;
              distText.textContent = 'Jarak tidak tersedia';
            }
          });

          cards.sort((a, b) => parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance));
          cards.forEach((card) => listEl.appendChild(card));
        },
        () => {
          btnTerdekat.classList.remove('active');
          alert('Izin lokasi ditolak. Aktifkan GPS browser untuk mengurutkan rental terdekat.');
        }
      );
    });

    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      }[m]));
    }
  </script>
</body>
</html>