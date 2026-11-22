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
$objGame    = new Game($pdo);
$pesan      = '';

if (isset($_POST['tambah_game'])) {
    $pesan = $objGame->tambahGame(
        $_POST['rental_console_id'],
        $_POST['game_name'],
        $_POST['genre']
    );
}

if (isset($_POST['ubah_status'])) {
    $pesan = $objGame->ubahStatus(
        $_POST['rental_game_id'],
        $rental_id,
        $_POST['status_baru']
    );
}

if (isset($_POST['hapus_game'])) {
    $pesan = $objGame->hapusGame($_POST['rental_game_id'], $rental_id);
}

$my_consoles  = $objConsole->getDaftarConsole($rental_id);
$master_games = $objGame->getMasterGames();
$daftar_game  = $objGame->getDaftarGame($rental_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Daftar Game</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Kelola Daftar Game</h2>
        <div>
            <a href="dashboard.php">&laquo; Dashboard</a>
            <a href="jadwal.php">Jadwal</a>
            <a href="konsol.php">Konsol & Harga</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box"><?= $pesan ?></div>
        <?php endif; ?>

        <?php if (count($my_consoles) > 0): ?>
            <div class="card">
                <h3>Tambah Game Berdasarkan Konsol</h3>
                <form method="POST" action="" class="form-inline">
                    <div class="form-group">
                        <label>Pilih Konsol</label>
                        <select name="rental_console_id" required>
                            <option value="">Pilih Konsol</option>
                            <?php foreach ($my_consoles as $myc): ?>
                                <option value="<?= $myc['rental_console_id'] ?>">
                                    <?= htmlspecialchars($myc['console_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Judul Game</label>
                        <input type="text" name="game_name" list="master-games" placeholder="Contoh: FC 25, GTA V" required>
                        <datalist id="master-games">
                            <?php foreach ($master_games as $mg): ?>
                                <option value="<?= htmlspecialchars($mg['game_name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label>Genre (Opsional)</label>
                        <input type="text" name="genre" placeholder="Contoh: Sport, Action">
                    </div>

                    <button type="submit" name="tambah_game" class="btn">Tambah Game</button>
                </form>
            </div>

            <div class="card">
                <h3>Daftar Game di Toko Anda</h3>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Konsol</th>
                            <th>Judul Game</th>
                            <th>Genre</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                        <?php if (empty($daftar_game)): ?>
                            <tr><td colspan="5" class="text-center">Belum ada game yang ditambahkan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($daftar_game as $g):
                                $status_berikutnya = ($g['status'] === 'tersedia') ? 'tidak tersedia' : 'tersedia';
                                $warna_status      = ($g['status'] === 'tersedia') ? '#16a34a' : '#dc2626';
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($g['console_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($g['game_name']) ?></td>
                                    <td><?= htmlspecialchars($g['genre'] ?? '-') ?></td>
                                    <td>
                                        <span style="color: <?= $warna_status ?>; font-weight: 600;">
                                            <?= ucfirst(htmlspecialchars($g['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <form method="POST" action="">
                                                <input type="hidden" name="rental_game_id" value="<?= $g['rental_game_id'] ?>">
                                                <input type="hidden" name="status_baru" value="<?= $status_berikutnya ?>">
                                                <button type="submit" name="ubah_status" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.82rem;">
                                                    <?= ($g['status'] === 'tersedia') ? 'Set Tidak Tersedia' : 'Set Tersedia' ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="" onsubmit="return confirm('Hapus game ini?');">
                                                <input type="hidden" name="rental_game_id" value="<?= $g['rental_game_id'] ?>">
                                                <button type="submit" name="hapus_game" class="btn" style="background-color: #dc2626; padding: 5px 10px; font-size: 0.82rem;">
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
        <?php else: ?>
            <div class="card">
                <p class="subtitle" style="margin-bottom: 12px;">
                    Tambahkan mesin konsol terlebih dahulu di menu Konsol & Harga sebelum menambahkan daftar game.
                </p>
                <a href="konsol.php" class="btn">Ke Menu Konsol & Harga</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>