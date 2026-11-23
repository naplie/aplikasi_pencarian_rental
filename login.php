<?php
session_start();
require_once 'koneksi.php';
require_once 'Models.php';

$pesan = '';

if (isset($_POST['login'])) {
    $objOwner = new Owner($pdo);
    try {
        if ($objOwner->login(trim($_POST['email']), $_POST['password'])) {
            if (($_SESSION['role'] ?? 'owner') === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
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
    <title>Login Owner</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h2>Direktori Rental console</h2>
        <a href="index.php">Beranda</a>
    </nav>

    <div class="auth-container">
        <div class="card">
            <h2>Login Owner Rental</h2>
            <p class="subtitle">Masuk untuk mengelola profil, jadwal, dan harga konsol.</p>

            <?php if (!empty($pesan)): ?>
                <div class="alert-box"><p style="color: #dc2626;"><?= htmlspecialchars($pesan) ?></p></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="owner@email.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>

                <button type="submit" name="login" class="btn btn-full">Login</button>
            </form>

            <p class="text-center mt-16" style="font-size: 0.9rem;">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>