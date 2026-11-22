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

$objJadwal = new JadwalBukaTutup($pdo);
$pesan     = '';

if (isset($_POST['simpan_jadwal'])) {
    $pesan = $objJadwal->simpanJadwal($rental_id, $_POST);
}

$jadwal_db = $objJadwal->getJadwal($rental_id);
$hari_list = $objJadwal->getHariList();

$daftar_jam = [];
for ($i = 0; $i < 24; $i++) {
    $daftar_jam[] = sprintf('%02d:00', $i);
    $daftar_jam[] = sprintf('%02d:30', $i);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Jadwal Buka/Tutup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Atur Jadwal Operasional</h2>
        <div>
            <a href="dashboard.php">&laquo; Dashboard</a>
            <a href="konsol.php">Konsol & Harga</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box"><?= $pesan ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Jadwal Buka/Tutup (Satu Minggu)</h3>
            <p class="subtitle">Pilih jam operasional 24 jam atau centang Tutup Seharian jika libur.</p>

            <form method="POST" action="">
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Hari</th>
                            <th>Jam Buka</th>
                            <th>Jam Tutup</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($hari_list as $hari):
                            $data_hari = $jadwal_db[$hari] ?? null;
                            $buka_val  = $data_hari && $data_hari['opening_time'] ? substr($data_hari['opening_time'], 0, 5) : '09:00';
                            $tutup_val = $data_hari && $data_hari['closing_time'] ? substr($data_hari['closing_time'], 0, 5) : '22:00';
                            $is_libur  = ($data_hari && $data_hari['is_closed'] == 1) ? 'checked' : '';
                        ?>
                        <tr>
                            <td><strong><?= $hari ?></strong></td>
                            <td>
                                <select name="buka[<?= $hari ?>]" id="buka_<?= $hari ?>" <?= $is_libur ? 'disabled' : '' ?>>
                                    <?php foreach ($daftar_jam as $jam): ?>
                                        <option value="<?= $jam ?>" <?= ($buka_val === $jam) ? 'selected' : '' ?>>
                                            <?= $jam ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="tutup[<?= $hari ?>]" id="tutup_<?= $hari ?>" <?= $is_libur ? 'disabled' : '' ?>>
                                    <?php foreach ($daftar_jam as $jam): ?>
                                        <option value="<?= $jam ?>" <?= ($tutup_val === $jam) ? 'selected' : '' ?>>
                                            <?= $jam ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="libur[<?= $hari ?>]" value="1" <?= $is_libur ?>
                                           onchange="toggleJam('<?= $hari ?>', this.checked)">
                                    Tutup Seharian
                                </label>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div class="mt-16">
                    <button type="submit" name="simpan_jadwal" class="btn">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleJam(hari, isLibur) {
            document.getElementById('buka_' + hari).disabled = isLibur;
            document.getElementById('tutup_' + hari).disabled = isLibur;
        }
    </script>
</body>
</html>