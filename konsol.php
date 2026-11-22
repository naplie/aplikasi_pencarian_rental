<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit;
}

$objRental = new Rental($pdo);
$rental    = $objRental->getByOwner($_SESSION['owner_id']);

if (!$rental) {
    die("Silakan lengkapi profil rental di dashboard terlebih dahulu!");
}
$rental_id = $rental['rental_id'];

$objConsole = new ConsoleDanHarga($pdo);
$pesan      = '';

if (isset($_POST['tambah_konsol'])) {
    $pesan = $objConsole->tambahConsole($rental_id, $_POST['console_id'], $_POST['total_units']);
}

if (isset($_POST['tambah_harga'])) {
    $pesan = $objConsole->tambahHarga($_POST['rental_console_id'], $_POST['durasi'], $_POST['harga']);
}
if (isset($_POST['hapus_harga'])) {
    $pesan = $objConsole->hapusHarga($_POST['rate_id'], $rental_id);
}

$master_consoles = $objConsole->getMasterConsole();
$my_consoles     = $objConsole->getDaftarConsole($rental_id);
$rates           = $objConsole->getDaftarHarga($rental_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Konsol & Harga</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Daftar Konsol & Harga Sewa</h2>
        <div>
            <a href="dashboard.php">&laquo; Dashboard</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box"><?= $pesan ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>1. Tambah Mesin Konsol ke Toko</h3>
            <form method="POST" action="" class="form-inline">
                <div class="form-group">
                    <label>Pilih Jenis Konsol</label>
                    <select name="console_id" required>
                        <option value="">Pilih</option>
                        <?php foreach ($master_consoles as $mc): ?>
                            <option value="<?= $mc['console_id'] ?>"><?= htmlspecialchars($mc['console_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="max-width: 140px;">
                    <label>Jumlah Unit</label>
                    <input type="number" name="total_units" min="1" placeholder="1" required>
                </div>

                <button type="submit" name="tambah_konsol" class="btn">Tambahkan Konsol</button>
            </form>
        </div>

        <?php if (count($my_consoles) > 0): ?>
            <div class="card">
                <h3>2. Atur Harga Sewa</h3>
                <form method="POST" action="" class="form-inline">
                    <div class="form-group">
                        <label>Pilih Konsol</label>
                        <select name="rental_console_id" required>
                            <?php foreach ($my_consoles as $myc): ?>
                                <option value="<?= $myc['rental_console_id'] ?>">
                                    <?= htmlspecialchars($myc['console_name']) ?> (<?= $myc['total_units'] ?> unit)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="max-width: 140px;">
                        <label>Durasi (Jam)</label>
                        <input type="number" name="durasi" min="1" placeholder="1" required>
                    </div>

                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" min="0" placeholder="Contoh: 5000" required>
                    </div>

                    <button type="submit" name="tambah_harga" class="btn">Tambah Harga</button>
                </form>
            </div>

            <div class="card">
                <h3>Daftar Harga Saat Ini</h3>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Konsol</th>
                            <th>Durasi</th>
                            <th>Harga</th>
                        </tr>
                        <?php if (empty($rates)): ?>
                            <tr><td colspan="5" class="text-center">Belum ada harga yang diatur.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rates as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['console_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['package_name']) ?></td>
                                    <td><?= $r['duration_hours'] ?> Jam</td>
                                    <td>Rp <?= number_format($r['price'], 0, ',', '.') ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus paket ini?');">
                                            <input type="hidden" name="rate_id" value="<?= $r['rate_id'] ?>">
                                            <button type="submit" name="hapus_harga" class="btn" style="background-color: #dc2626; padding: 6px 12px; font-size: 0.85rem;">
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
        <?php else: ?>
            <div class="card">
                <p class="subtitle" style="margin-bottom: 0;">*Tambahkan mesin konsol terlebih dahulu untuk mengatur harga sewa.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>