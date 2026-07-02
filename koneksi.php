<?php

function loadEnvFile($filePath)
{
    if (!is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '' || $value === '') {
            continue;
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

loadEnvFile(__DIR__ . '/.env');

// Konfigurasi database MySQL
// Ganti nilai di bawah ini sesuai dengan database hosting Anda.
// Jika Anda menggunakan cPanel/MySQL hosting, biasanya DB_HOST = 'localhost'.

$envHost = getenv('DB_HOST');
$envPort = getenv('DB_PORT');
$envName = getenv('DB_NAME');
$envUser = getenv('DB_USER');
$envPass = getenv('DB_PASS');
$envCharset = getenv('DB_CHARSET');

define('DB_HOST', $envHost !== false && $envHost !== '' ? $envHost : 'localhost');
define('DB_PORT', $envPort !== false && $envPort !== '' ? intval($envPort) : 3306);
define('DB_NAME', $envName !== false && $envName !== '' ? $envName : 'nama_database_anda');
define('DB_USER', $envUser !== false && $envUser !== '' ? $envUser : 'nama_user_anda');
define('DB_PASS', $envPass !== false ? $envPass : 'password_anda');
define('DB_CHARSET', $envCharset !== false && $envCharset !== '' ? $envCharset : 'utf8mb4');

define('DEBUG_DB', true); // Ubah ke false setelah koneksi berhasil

function validate_db_config()
{
    $placeholders = [
        'nama_database_anda',
        'nama_user_anda',
        'password_anda'
    ];

    if (in_array(DB_NAME, $placeholders, true) || in_array(DB_USER, $placeholders, true) || in_array(DB_PASS, $placeholders, true)) {
        throw new Exception('Silakan perbarui koneksi.php dengan database hosting Anda: DB_NAME, DB_USER, DB_PASS.');
    }
}

function get_db()
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        validate_db_config();
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Fallback jika koneksi 127.0.0.1 gagal, coba localhost.
        if (DB_HOST === '127.0.0.1') {
            try {
                $dsn = 'mysql:host=localhost;port=' . DB_PORT . ';charset=' . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e2) {
                throw new Exception('Gagal terhubung ke database MySQL: ' . $e2->getMessage());
            }
        } else {
            throw new Exception('Gagal terhubung ke database MySQL: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        throw $e;
    }

    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_general_ci');
        $pdo->exec('USE `' . DB_NAME . '`');

        initialize_schema($pdo);

        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Gagal terhubung ke database MySQL: ' . $e->getMessage());
    }
}

function initialize_schema(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS warga (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        no_kk VARCHAR(50),
        nik VARCHAR(50),
        nama VARCHAR(255),
        jk VARCHAR(2),
        kerja VARCHAR(100),
        hp VARCHAR(50),
        alamat TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $existingUsername = $pdo->query("SHOW COLUMNS FROM warga LIKE 'username'")->fetch(PDO::FETCH_ASSOC);
    if (!$existingUsername) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN username VARCHAR(100) UNIQUE NULL AFTER id");
    }
    $existingPassword = $pdo->query("SHOW COLUMNS FROM warga LIKE 'password'")->fetch(PDO::FETCH_ASSOC);
    if (!$existingPassword) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN password VARCHAR(255) NULL AFTER username");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS pengumuman (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tgl VARCHAR(50),
        judul VARCHAR(255),
        konten TEXT,
        penulis VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS keluhan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tgl VARCHAR(50),
        user VARCHAR(100),
        nama VARCHAR(255),
        kategori VARCHAR(100),
        deskripsi TEXT,
        foto LONGBLOB,
        foto_admin LONGBLOB,
        status VARCHAR(100),
        tanggapan TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS surat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tgl VARCHAR(50),
        user VARCHAR(100),
        nama VARCHAR(255),
        jenis VARCHAR(255),
        ket TEXT,
        status VARCHAR(100)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS keuangan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tgl VARCHAR(50),
        jenis VARCHAR(100),
        kategori VARCHAR(100),
        ket TEXT,
        masuk INT,
        keluar INT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS iuran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_warga INT,
        user VARCHAR(100),
        nama VARCHAR(255),
        bulan VARCHAR(50),
        jumlah INT,
        nominal INT,
        status VARCHAR(100),
        FOREIGN KEY (id_warga) REFERENCES warga(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default users jika tabel users kosong
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || intval($row['cnt']) === 0) {
        $insert = $pdo->prepare('INSERT INTO users (username, password, nama, role) VALUES (:username, :password, :nama, :role)');
        $insert->execute([':username' => 'admin', ':password' => '123', ':nama' => 'Bpk. Budi (Ketua RT)', ':role' => 'admin']);
        $insert->execute([':username' => 'peserta', ':password' => '123', ':nama' => 'Sdr. Andi (Warga)', ':role' => 'warga']);
    }

    insert_sample_data($pdo);
}

function insert_sample_data(PDO $pdo)
{
    if (!table_empty($pdo, 'users')) {
        return;
    }

    $users = [
        ['admin', '123', 'Bpk. Budi (Ketua RT)', 'admin'],
        ['peserta', '123', 'Sdr. Andi (Warga)', 'warga']
    ];
    $stmt = $pdo->prepare('INSERT INTO users (username, password, nama, role) VALUES (:username, :password, :nama, :role)');
    foreach ($users as $user) {
        $stmt->execute([':username' => $user[0], ':password' => $user[1], ':nama' => $user[2], ':role' => $user[3]]);
    }

    $warga = [
        ['320101010101', '320101010102', 'Andi S', 'L', 'Karyawan', '0811', 'Blok A/1'],
        ['320101010101', '320101010103', 'Siti M', 'P', 'IRT', '0812', 'Blok A/1']
    ];
    $stmt = $pdo->prepare('INSERT INTO warga (no_kk, nik, nama, jk, kerja, hp, alamat) VALUES (:no_kk, :nik, :nama, :jk, :kerja, :hp, :alamat)');
    foreach ($warga as $row) {
        $stmt->execute([':no_kk' => $row[0], ':nik' => $row[1], ':nama' => $row[2], ':jk' => $row[3], ':kerja' => $row[4], ':hp' => $row[5], ':alamat' => $row[6]]);
    }

    $stmt = $pdo->prepare('INSERT INTO pengumuman (tgl, judul, konten, penulis) VALUES (:tgl, :judul, :konten, :penulis)');
    $stmt->execute([':tgl' => '26/05/2026', ':judul' => 'Kerja Bakti Minggu Depan', ':konten' => 'Mohon kehadiran seluruh bapak-bapak untuk membersihkan selokan di blok A.', ':penulis' => 'Admin RT']);

    $stmt = $pdo->prepare('INSERT INTO keluhan (tgl, user, nama, kategori, deskripsi, status, tanggapan) VALUES (:tgl, :user, :nama, :kategori, :deskripsi, :status, :tanggapan)');
    $stmt->execute([':tgl' => '25/05/2026', ':user' => 'peserta', ':nama' => 'Sdr. Andi', ':kategori' => 'Infrastruktur', ':deskripsi' => 'Lampu jalan depan Blok A mati.', ':status' => 'Diproses', ':tanggapan' => 'Tukang sedang dipanggil.']);

    $stmt = $pdo->prepare('INSERT INTO iuran (user, nama, bulan, nominal, status) VALUES (:user, :nama, :bulan, :nominal, :status)');
    $stmt->execute([':user' => 'peserta', ':nama' => 'Sdr. Andi', ':bulan' => '05-2026', ':nominal' => 50000, ':status' => 'Belum Lunas']);
    $stmt->execute([':user' => 'peserta', ':nama' => 'Sdr. Andi', ':bulan' => '04-2026', ':nominal' => 50000, ':status' => 'Lunas']);

    $stmt = $pdo->prepare('INSERT INTO keuangan (tgl, jenis, kategori, ket, masuk, keluar) VALUES (:tgl, :jenis, :kategori, :ket, :masuk, :keluar)');
    $stmt->execute([':tgl' => '01/05/2026', ':jenis' => 'Pemasukan', ':kategori' => 'Iuran', ':ket' => 'Iuran April Blok B', ':masuk' => 1500000, ':keluar' => 0]);
    $stmt->execute([':tgl' => '05/05/2026', ':jenis' => 'Pengeluaran', ':kategori' => 'Keamanan', ':ket' => 'Gaji Satpam', ':masuk' => 0, ':keluar' => 1000000]);

    $stmt = $pdo->prepare('INSERT INTO surat (tgl, user, nama, jenis, ket, status) VALUES (:tgl, :user, :nama, :jenis, :ket, :status)');
    $stmt->execute([':tgl' => '24/05/2026', ':user' => 'peserta', ':nama' => 'Sdr. Andi', ':jenis' => 'Surat Pengantar KTP', ':ket' => 'Perpanjang', ':status' => 'Selesai']);
}

function table_empty(PDO $pdo, $table)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM `' . $table . '`');
    $stmt->execute();
    $row = $stmt->fetch();
    return intval($row['count']) === 0;
}
