<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

// Proteksi: hanya akun dengan role 'admin' yang boleh mengakses halaman ini
if (!isset($_SESSION['owner_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$objAdmin = new Admin($pdo);
$pesan    = '';

if (isset($_POST['hapus_rental']) && is_numeric($_POST['rental_id'])) {
    $pesan = $objAdmin->hapusRental((int)$_POST['rental_id']);
}

if (isset($_POST['hapus_ulasan']) && is_numeric($_POST['rating_id'])) {
    $pesan = $objAdmin->hapusUlasan((int)$_POST['rating_id']);
}

$daftar_rental = $objAdmin->getSemuaRental();
$daftar_ulasan = $objAdmin->getSemuaUlasan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Super Admin — RentalPS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Panel Admin — <?= htmlspecialchars($_SESSION['owner_name'] ?? 'Admin') ?></h2>
        <div>
            <a href="index.php">Lihat Website</a>
            <a href="admin.php">Daftar Rental</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box"><?= $pesan ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Ringkasan Sistem</h3>
            <p class="subtitle" style="margin-bottom: 0;">
                <strong><?= htmlspecialchars($objAdmin->getNamaModul()) ?>:</strong>
                <?= htmlspecialchars($objAdmin->getRingkasan()) ?>
            </p>
        </div>

        <div class="card">
            <h3>Moderasi Seluruh Tempat Rental</h3>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nama Rental</th>
                        <th>Pemilik</th>
                        <th>Alamat</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (empty($daftar_rental)): ?>
                        <tr><td colspan="7" class="text-center">Belum ada tempat rental yang terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daftar_rental as $r): ?>
                            <tr>
                                <td>#<?= (int)$r['rental_id'] ?></td>
                                <td><strong><?= htmlspecialchars($r['rental_name']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($r['owner_name']) ?><br>
                                    <small style="color: #6c7280;"><?= htmlspecialchars($r['owner_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['address'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($r['avg_rating'])): ?>
                                        <strong style="color: #d97706;">★ <?= number_format((float)$r['avg_rating'], 1) ?></strong>
                                        <small style="color: #6c7280;">(<?= (int)$r['total_reviews'] ?>)</small>
                                    <?php else: ?>
                                        <small style="color: #6c7280;">Belum ada</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($r['status_toko'] ?? 'buka')) ?></td>
                                <td>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                        <a href="detail.php?id=<?= (int)$r['rental_id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.82rem;">
                                            Detail
                                        </a>
                                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus tempat rental ini beserta semua datanya?');">
                                            <input type="hidden" name="rental_id" value="<?= (int)$r['rental_id'] ?>">
                                            <button type="submit" name="hapus_rental" class="btn" style="background-color: #dc2626; border-color: #dc2626; padding: 5px 10px; font-size: 0.82rem;">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Moderasi Ulasan &amp; Rating Pengunjung</h3>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Tempat Rental</th>
                        <th>Pengulas</th>
                        <th>Rating</th>
                        <th>Ulasan</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (empty($daftar_ulasan)): ?>
                        <tr><td colspan="6" class="text-center">Belum ada ulasan dari pengunjung.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daftar_ulasan as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['rental_name']) ?></strong></td>
                                <td><?= htmlspecialchars($u['reviewer_name']) ?></td>
                                <td style="color: #d97706; white-space: nowrap;">
                                    <?= str_repeat('★', (int)$u['rating']) . str_repeat('☆', 5 - (int)$u['rating']) ?>
                                </td>
                                <td><?= nl2br(htmlspecialchars($u['review_text'] ?? '-')) ?></td>
                                <td style="white-space: nowrap; color: #6c7280; font-size: 0.85rem;">
                                    <?= date('d M Y, H:i', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <form method="POST" action="" onsubmit="return confirm('Hapus ulasan ini?');">
                                        <input type="hidden" name="rating_id" value="<?= (int)$u['rating_id'] ?>">
                                        <button type="submit" name="hapus_ulasan" class="btn" style="background-color: #dc2626; border-color: #dc2626; padding: 5px 10px; font-size: 0.82rem;">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>