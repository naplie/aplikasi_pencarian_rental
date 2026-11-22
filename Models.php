<?php
// Parent class (Abstract)
abstract class BaseModel {
    // Encapsulation (protected): hanya bisa diakses class ini dan class turunannya
    protected $pdo;

    // Encapsulation data hiding (private): tersembunyi sepenuhnya dari luar class
    private $namaModul;

    public function __construct($pdo_connect, $namaModul) {
        $this->pdo = $pdo_connect;
        $this->setNamaModul($namaModul);
    }

    // Getter & Setter untuk mengakses attribute private secara aman
    public function getNamaModul() {
        return $this->namaModul;
    }

    public function setNamaModul($nama) {
        if (!empty(trim($nama))) {
            $this->namaModul = trim($nama);
        }
    }

    // Polymorphism
    abstract public function getRingkasan($id);
}


// Child class: Owner (Inheritance dari BaseModel)
class Owner extends BaseModel {
    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Akun Owner');
    }

    public function register($name, $email, $password, $phone = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO owners (name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $phone]);
            return true;
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                throw new Exception("Email sudah terdaftar!");
            }
            throw new Exception("Error Database: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM owners WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Email tidak ditemukan.");
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception("Password salah.");
        }

        $_SESSION['owner_id']    = $user['owner_id'];
        $_SESSION['owner_name']  = $user['name'];
        $_SESSION['owner_email'] = $user['email'];
        return true;
    }

    // Override (Polymorphism)
    public function getRingkasan($owner_id) {
        $stmt = $this->pdo->prepare("SELECT name, email FROM owners WHERE owner_id = ?");
        $stmt->execute([$owner_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? "{$row['name']} ({$row['email']})" : "Akun tidak ditemukan.";
    }
}


// Child class: Rental (Inheritance dari BaseModel)
class Rental extends BaseModel {
    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Profil & Galeri');
    }

    public function getByOwner($owner_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.rental_id, r.rental_name, r.catatan, l.address, l.latitude, l.longitude
            FROM rentals r
            LEFT JOIN locations l ON r.rental_id = l.rental_id
            WHERE r.owner_id = ?
        ");
        $stmt->execute([$owner_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function simpanCatatan($rental_id, $catatan) {
        $catatan = trim($catatan);
        $catatan = ($catatan === '') ? null : $catatan;

        try {
            $stmt = $this->pdo->prepare("UPDATE rentals SET catatan = ? WHERE rental_id = ?");
            $stmt->execute([$catatan, $rental_id]);
            return "<p style='color: #16a34a;'>Catatan berhasil disimpan!</p>";
        } catch (PDOException $e) {
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function simpanProfil($owner_id, $nama_rental, $alamat, $latitude, $longitude) {
        try {
            $this->pdo->beginTransaction();

            $stmt_rental = $this->pdo->prepare("INSERT INTO rentals (owner_id, rental_name) VALUES (?, ?)");
            $stmt_rental->execute([$owner_id, $nama_rental]);
            $rental_id = $this->pdo->lastInsertId();

            $stmt_lokasi = $this->pdo->prepare("INSERT INTO locations (rental_id, address, latitude, longitude) VALUES (?, ?, ?, ?)");
            $stmt_lokasi->execute([$rental_id, $alamat, $latitude, $longitude]);

            $this->pdo->commit();
            return "<p style='color: #16a34a;'>Profil rental dan lokasi berhasil disimpan!</p>";
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function uploadFoto($rental_id, $file, $caption) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $nama_file_baru = time() . '_' . basename($file['name']);
        $target_file    = $target_dir . $nama_file_baru;
        $fileType       = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $valid_ext      = ["jpg", "jpeg", "png", "webp"];

        if (!in_array($fileType, $valid_ext)) {
            return "<p style='color: #dc2626;'>Format file salah! Hanya boleh JPG, PNG, atau WEBP.</p>";
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $stmt = $this->pdo->prepare("INSERT INTO rental_photos (rental_id, photo_url, caption) VALUES (?, ?, ?)");
            $stmt->execute([$rental_id, $target_file, $caption]);
            return "<p style='color: #16a34a;'>Foto berhasil ditambahkan!</p>";
        }

        return "<p style='color: #dc2626;'>Gagal mengunggah foto.</p>";
    }

    public function getFoto($rental_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rental_photos WHERE rental_id = ? ORDER BY photo_id DESC");
        $stmt->execute([$rental_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Override (Polymorphism)
    public function getRingkasan($rental_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rental_photos WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        $jumlahFoto = $stmt->fetchColumn();
        return "Profil aktif dengan {$jumlahFoto} foto di galeri.";
    }

    public function getSemuaJenisKonsol() {
        return $this->pdo->query("SELECT console_id, console_name FROM consoles ORDER BY console_name ASC")
                         ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cariRental($keyword = '', $console_id = '') {
        $sql = "
            SELECT
                r.rental_id,
                r.rental_name,
                r.catatan,
                o.phone,
                l.address,
                l.latitude,
                l.longitude,
                (SELECT rp.photo_url FROM rental_photos rp WHERE rp.rental_id = r.rental_id LIMIT 1) AS thumbnail,
                (SELECT GROUP_CONCAT(DISTINCT c.console_name ORDER BY c.console_name SEPARATOR ', ')
                 FROM rental_consoles rc
                 JOIN consoles c ON rc.console_id = c.console_id
                 WHERE rc.rental_id = r.rental_id) AS daftar_konsol,
                (SELECT MIN(rr.price)
                 FROM rental_rates rr
                 JOIN rental_consoles rc ON rr.rental_console_id = rc.rental_console_id
                 WHERE rc.rental_id = r.rental_id) AS harga_terendah
            FROM rentals r
            JOIN locations l ON r.rental_id = l.rental_id
            JOIN owners o ON r.owner_id = o.owner_id
            WHERE 1=1
        ";

        $params  = [];
        $keyword = trim($keyword);

        if ($keyword !== '') {
            $like = "%{$keyword}%";
            $sql .= "
                AND (
                    r.rental_name LIKE ?
                    OR l.address LIKE ?
                    OR EXISTS (
                        SELECT 1
                        FROM rental_consoles rc_g
                        JOIN rental_games rg_g ON rc_g.rental_console_id = rg_g.rental_console_id
                        JOIN games g_g ON rg_g.game_id = g_g.game_id
                        WHERE rc_g.rental_id = r.rental_id
                          AND rg_g.status = 'tersedia'
                          AND g_g.game_name LIKE ?
                    )
                )
            ";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($console_id !== '' && is_numeric($console_id)) {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM rental_consoles rc_c
                    WHERE rc_c.rental_id = r.rental_id
                      AND rc_c.console_id = ?
                )
            ";
            $params[] = (int)$console_id;
        }

        $sql .= " ORDER BY r.rental_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getDetailById($rental_id) {
        $stmt = $this->pdo->prepare("
            SELECT
                r.rental_id,
                r.rental_name,
                r.catatan,
                o.name AS owner_name,
                o.phone,
                l.address,
                l.latitude,
                l.longitude
            FROM rentals r
            JOIN owners o ON r.owner_id = o.owner_id
            LEFT JOIN locations l ON r.rental_id = l.rental_id
            WHERE r.rental_id = ?
        ");
        $stmt->execute([$rental_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


// Child class: JadwalBukaTutup (Inheritance dari BaseModel)
class JadwalBukaTutup extends BaseModel {
    private $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Jadwal Operasional');
    }

    public function getHariList() {
        return $this->hariList;
    }

    public function getJadwal($rental_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rental_schedules WHERE rental_id = ?");
        $stmt->execute([$rental_id]);

        $hasil = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hasil[$row['day_of_week']] = $row;
        }
        return $hasil;
    }

    public function simpanJadwal($rental_id, $data_post) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO rental_schedules (rental_id, day_of_week, opening_time, closing_time, is_closed)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                opening_time = VALUES(opening_time),
                closing_time = VALUES(closing_time),
                is_closed = VALUES(is_closed)
            ");

            foreach ($this->hariList as $hari) {
                $is_libur = isset($data_post['libur'][$hari]) ? 1 : 0;
                $buka     = $is_libur ? null : $data_post['buka'][$hari];
                $tutup    = $is_libur ? null : $data_post['tutup'][$hari];

                $stmt->execute([$rental_id, $hari, $buka, $tutup, $is_libur]);
            }

            $this->pdo->commit();
            return "<p style='color: #16a34a;'>Jadwal berhasil disimpan!</p>";
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    // Override (Polymorphism)
    public function getRingkasan($rental_id) {
        $jadwal = $this->getJadwal($rental_id);
        if (empty($jadwal)) {
            return "Jadwal belum diatur.";
        }

        $buka = 0;
        $libur = 0;
        foreach ($jadwal as $hari) {
            if ($hari['is_closed'] == 1) {
                $libur++;
            } else {
                $buka++;
            }
        }
        return "Buka {$buka} hari/minggu, libur {$libur} hari.";
    }
}


// Child class: ConsoleDanHarga (Inheritance dari BaseModel)
class ConsoleDanHarga extends BaseModel {
    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Konsol & Tarif');
    }

    public function getMasterConsole() {
        $data = $this->pdo->query("SELECT * FROM consoles")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            $stmt = $this->pdo->prepare("INSERT INTO consoles (console_name, brand, generation) VALUES (?, ?, ?)");
            $default_consoles = [
                ['PS 3', 'Sony', '7th Gen'],
                ['PS 4', 'Sony', '8th Gen'],
                ['PS 5', 'Sony', '9th Gen']
            ];
            foreach ($default_consoles as $c) {
                $stmt->execute($c);
            }
            $data = $this->pdo->query("SELECT * FROM consoles")->fetchAll(PDO::FETCH_ASSOC);
        }

        return $data;
    }

    public function getDaftarConsole($rental_id) {
        $stmt = $this->pdo->prepare("
            SELECT rc.rental_console_id, c.console_name, rc.total_units, rc.available_units, rc.status
            FROM rental_consoles rc
            JOIN consoles c ON rc.console_id = c.console_id
            WHERE rc.rental_id = ?
        ");
        $stmt->execute([$rental_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDaftarHarga($rental_id) {
        $stmt = $this->pdo->prepare("
            SELECT rr.rate_id, c.console_name, rr.package_name, rr.duration_hours, rr.price
            FROM rental_rates rr
            JOIN rental_consoles rc ON rr.rental_console_id = rc.rental_console_id
            JOIN consoles c ON rc.console_id = c.console_id
            WHERE rc.rental_id = ?
            ORDER BY c.console_name ASC, rr.duration_hours ASC
        ");
        $stmt->execute([$rental_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tambahConsole($rental_id, $console_id, $total_units) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rental_consoles (rental_id, console_id, total_units, available_units)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$rental_id, $console_id, $total_units, $total_units]);
            return "<p style='color: #16a34a;'>Konsol berhasil ditambahkan!</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return "<p style='color: #dc2626;'>Konsol ini sudah ada di daftar tokomu.</p>";
            }
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function tambahHarga($rental_console_id, $durasi, $harga) {
        $nama_paket = "Paket " . $durasi . " Jam";

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rental_rates (rental_console_id, package_name, duration_hours, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$rental_console_id, $nama_paket, $durasi, $harga]);
            return "<p style='color: #16a34a;'>Harga berhasil ditambahkan!</p>";
        } catch (PDOException $e) {
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function hapusHarga($rate_id, $rental_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE rr FROM rental_rates rr
                JOIN rental_consoles rc ON rr.rental_console_id = rc.rental_console_id
                WHERE rr.rate_id = ? AND rc.rental_id = ?
            ");
            $stmt->execute([$rate_id, $rental_id]);
            return "<p style='color: #16a34a;'>Paket harga berhasil dihapus!</p>";
        } catch (PDOException $e) {
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    // Override (Polymorphism)
    public function getRingkasan($rental_id) {
        $konsol = $this->getDaftarConsole($rental_id);
        $harga  = $this->getDaftarHarga($rental_id);
        $totalUnit = array_sum(array_column($konsol, 'total_units'));

        return count($konsol) . " jenis konsol ({$totalUnit} unit), " . count($harga) . " paket tarif.";
    }
}


// Child class: Game (Inheritance dari BaseModel)
class Game extends BaseModel {
    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Daftar Game');
    }

    public function getMasterGames() {
        return $this->pdo->query("SELECT game_id, game_name, genre FROM games ORDER BY game_name ASC")
                         ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDaftarGame($rental_id) {
        $stmt = $this->pdo->prepare("
            SELECT rg.rental_game_id, c.console_name, g.game_name, g.genre, rg.status
            FROM rental_games rg
            JOIN rental_consoles rc ON rg.rental_console_id = rc.rental_console_id
            JOIN consoles c ON rc.console_id = c.console_id
            JOIN games g ON rg.game_id = g.game_id
            WHERE rc.rental_id = ?
            ORDER BY c.console_name ASC, g.game_name ASC
        ");
        $stmt->execute([$rental_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tambahGame($rental_console_id, $game_name, $genre = null) {
        $game_name = trim($game_name);
        $genre     = trim($genre ?? '') !== '' ? trim($genre) : null;

        if ($game_name === '') {
            return "<p style='color: #dc2626;'>Judul game tidak boleh kosong.</p>";
        }

        try {
            $this->pdo->beginTransaction();

            $stmt_cek = $this->pdo->prepare("SELECT game_id FROM games WHERE LOWER(game_name) = LOWER(?) LIMIT 1");
            $stmt_cek->execute([$game_name]);
            $game = $stmt_cek->fetch(PDO::FETCH_ASSOC);

            if ($game) {
                $game_id = $game['game_id'];
            } else {
                $stmt_new = $this->pdo->prepare("INSERT INTO games (game_name, genre) VALUES (?, ?)");
                $stmt_new->execute([$game_name, $genre]);
                $game_id = $this->pdo->lastInsertId();
            }

            $stmt_link = $this->pdo->prepare("
                INSERT INTO rental_games (rental_console_id, game_id, status)
                VALUES (?, ?, 'tersedia')
            ");
            $stmt_link->execute([$rental_console_id, $game_id]);

            $this->pdo->commit();
            return "<p style='color: #16a34a;'>Game berhasil ditambahkan!</p>";
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() == 23000) {
                return "<p style='color: #dc2626;'>Game ini sudah terdaftar di konsol tersebut.</p>";
            }
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function ubahStatus($rental_game_id, $rental_id, $status_baru) {
        if (!in_array($status_baru, ['tersedia', 'tidak tersedia'], true)) {
            return "<p style='color: #dc2626;'>Status tidak valid.</p>";
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE rental_games rg
                JOIN rental_consoles rc ON rg.rental_console_id = rc.rental_console_id
                SET rg.status = ?
                WHERE rg.rental_game_id = ? AND rc.rental_id = ?
            ");
            $stmt->execute([$status_baru, $rental_game_id, $rental_id]);
            return "<p style='color: #16a34a;'>Status game diperbarui.</p>";
        } catch (PDOException $e) {
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    public function hapusGame($rental_game_id, $rental_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE rg FROM rental_games rg
                JOIN rental_consoles rc ON rg.rental_console_id = rc.rental_console_id
                WHERE rg.rental_game_id = ? AND rc.rental_id = ?
            ");
            $stmt->execute([$rental_game_id, $rental_id]);
            return "<p style='color: #16a34a;'>Game berhasil dihapus.</p>";
        } catch (PDOException $e) {
            return "<p style='color: #dc2626;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    // Override wajib dari BaseModel (Polymorphism)
    public function getRingkasan($rental_id) {
        $games = $this->getDaftarGame($rental_id);
        $total = count($games);

        if ($total === 0) {
            return "Belum ada daftar game.";
        }

        $tersedia = 0;
        foreach ($games as $g) {
            if ($g['status'] === 'tersedia') {
                $tersedia++;
            }
        }
        return "{$total} judul game ({$tersedia} tersedia).";
    }
}


// Child class: RatingTempat (Inheritance dari BaseModel)
class rating_tempat extends BaseModel {
    public function __construct($pdo_connect) {
        parent::__construct($pdo_connect, 'Rating Tempat');
    }

    public function getRating($rental_id) {
        $stmt = $this->pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM rental_ratings WHERE rental_id = ?");
        $stmt->execute([$rental_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Override wajib dari BaseModel (Polymorphism)
    public function getRingkasan($rental_id) {
        $ratingData   = $this->getRating($rental_id);
        $totalReviews = (int)($ratingData['total_reviews'] ?? 0);

        if ($totalReviews === 0) {
            return "Belum ada ulasan.";
        }

        $avgRating = round((float)$ratingData['avg_rating'], 1);
        return "Rating rata-rata: {$avgRating} dari {$totalReviews} ulasan.";
    }
}