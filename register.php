<?php
require_once 'koneksi.php';
require_once 'Models.php';

$pesan = '';

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone'] ?? '') !== '' ? trim($_POST['phone']) : null;

    $objOwner = new Owner($pdo); // Instansiasi Object
    try {
        if ($objOwner->register($name, $email, $password, $phone)) {
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {
        $pesan = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Owner</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Direktori Rental PlayStation</h2>
        <a href="index.php">Beranda</a>
    </nav>

    <div class="auth-container">
        <div class="card">
            <h2>Daftar Akun Owner</h2>
            <p class="subtitle">Daftarkan tempat rental PlayStation kamu secara gratis.</p>

            <?php if (!empty($pesan)): ?>
                <div class="alert-box"><p style="color: #dc2626;"><?= htmlspecialchars($pesan) ?></p></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" placeholder="Nama pemilik rental" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="owner@email.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Buat password" required>
                </div>

                <div class="form-group">
                    <label>Nomor Telepon / WhatsApp <span style="font-weight: normal; color: #64748b;">(Opsional)</span></label>
                    <input type="text" name="phone" placeholder="Boleh dikosongkan">
                </div>

                <button type="submit" name="register" class="btn btn-full">Daftar</button>
            </form>

            <p class="text-center mt-16" style="font-size: 0.9rem;">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>