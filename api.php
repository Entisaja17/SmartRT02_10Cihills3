<?php
// Mencegah output HTML/spasi yang bocor
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ubah ke 1 HANYA saat debugging

// Set Header agar mengembalikan format JSON dan mengizinkan CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Health check untuk deteksi API dari frontend
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['status' => 'ok', 'message' => 'API siap']);
    exit;
}

// 1. Konfigurasi Database (Sesuaikan dengan server Anda)
function loadEnvFile($filePath) {
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

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'db_smart_rt'; // Nama database Anda
$user = getenv('DB_USER') ?: 'root';     // Username database Anda
$pass = getenv('DB_PASS') ?: '';         // Password database Anda (kosongkan jika default XAMPP)
$port = getenv('DB_PORT') ?: '3306';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$debug = getenv('DEBUG_MODE') === '1' || getenv('DEBUG_MODE') === 'true';

// Coba koneksi ke database
try {
    $dsn = "mysql:host=$host;port=$port;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE ${charset}_general_ci");
    $pdo->exec("USE `$db`");
    
    // Auto-migrate: Tambahkan kolom foto dan foto_admin ke table keluhan jika belum ada
    try {
        $checkFoto = $pdo->query("SHOW COLUMNS FROM keluhan LIKE 'foto'")->fetch();
        if (!$checkFoto) {
            $pdo->exec("ALTER TABLE keluhan ADD COLUMN foto LONGTEXT DEFAULT NULL AFTER deskripsi");
        }
        
        $checkFotoAdmin = $pdo->query("SHOW COLUMNS FROM keluhan LIKE 'foto_admin'")->fetch();
        if (!$checkFotoAdmin) {
            $pdo->exec("ALTER TABLE keluhan ADD COLUMN foto_admin LONGTEXT DEFAULT NULL AFTER foto");
        }
    } catch (Exception $e) {
        // Silent fail - table might not exist yet
    }
} catch (PDOException $e) {
    $errorPayload = ['status' => 'error', 'message' => 'Koneksi database gagal.'];
    if ($debug) {
        $errorPayload['debug'] = [
            'dsn' => $dsn,
            'error' => $e->getMessage(),
        ];
    }
    echo json_encode($errorPayload);
    exit;
}

// 2. Tangkap Data JSON dari Frontend (setara dengan e.postData.contents)
$inputJSON = file_get_contents('php://input');
$body = json_decode($inputJSON, true);

$action = $body['action'] ?? '';
$payload = $body['payload'] ?? [];
$result = [];

// 3. Routing Berdasarkan Action
try {
    if ($action === 'login') {
        $username = $payload['username'] ?? '';
        $password = $payload['password'] ?? '';

        // Cari user berdasarkan Username (frontend mengirimkan `username`)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Cek apakah user ada dan password cocok di tabel users
        if ($userData && $userData['password'] === $password) {
            $result = [
                'status' => 'success',
                'data' => [
                    'username' => $userData['username'],
                    'nama' => $userData['nama'],
                    'role' => strtolower($userData['role'])
                ]
            ];
        } else {
            $result = ['status' => 'error', 'message' => 'Username atau Password salah!'];
        }

    } elseif ($action === 'register') {
        $nama = $payload['nama'] ?? '';
        $usernameReg = $payload['username'] ?? ''; // frontend mengirimkan 'username'
        $password = $payload['password'] ?? '';

        // Cek apakah username sudah terdaftar
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmtCek->execute([$usernameReg]);
        
        if ($stmtCek->fetch()) {
            $result = ['status' => 'error', 'message' => 'Username/NIK sudah terdaftar.'];
        } else {
            // Jika belum ada, masukkan data baru ke kolom `username`
            $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, 'warga')");
            $stmtInsert->execute([$usernameReg, $password, $nama]);

            // Buat tagihan iuran otomatis agar langsung muncul di Manajemen Iuran Warga
            $bulanIuran = date('m-Y');
            $nominalIuran = 76000;
            $statusIuran = 'Belum Lunas';
            $stmtIuran = $pdo->prepare("INSERT INTO iuran (user, nama, bulan, nominal, status) VALUES (?, ?, ?, ?, ?)");
            $stmtIuran->execute([$usernameReg, $nama, $bulanIuran, $nominalIuran, $statusIuran]);
            
            $result = ['status' => 'success', 'message' => 'Pendaftaran berhasil.'];
        }

    } elseif ($action === 'getPengumuman') {
        $stmt = $pdo->query("SELECT id, tgl, judul, konten, penulis FROM pengumuman ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];

    } elseif ($action === 'addPengumuman') {
        $p = $payload;
        $tgl = $p['tgl'] ?? date('d/m/Y');
        $judul = $p['judul'] ?? '';
        $konten = $p['konten'] ?? '';
        $penulis = $p['penulis'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO pengumuman (tgl, judul, konten, penulis) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tgl, $judul, $konten, $penulis]);
        $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

        // Backward-compatible alias: frontend may call 'addBerita'
        } elseif ($action === 'addBerita') {
            $p = $payload;
            $tgl = $p['tgl'] ?? date('d/m/Y');
            $judul = $p['judul'] ?? '';
            $konten = $p['konten'] ?? '';
            $penulis = $p['penulis'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO pengumuman (tgl, judul, konten, penulis) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tgl, $judul, $konten, $penulis]);
            $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

    } elseif ($action === 'getWarga') {
        $stmt = $pdo->query("SELECT * FROM warga ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];

    } elseif ($action === 'changePassword') {
        $p = $payload;
        $username = trim($p['username'] ?? '');
        $oldPassword = $p['oldPassword'] ?? '';
        $newPassword = $p['newPassword'] ?? '';

        if ($username === '' || $oldPassword === '' || $newPassword === '') {
            throw new Exception('Username, password lama, dan password baru wajib diisi.');
        }
        if ($oldPassword === $newPassword) {
            throw new Exception('Password baru harus berbeda dari password lama.');
        }

        $stmtUser = $pdo->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
        $stmtUser->execute([$username]);
        $existingUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            throw new Exception('Akun tidak ditemukan.');
        }

        if ($existingUser['password'] !== $oldPassword) {
            throw new Exception('Password lama tidak cocok.');
        }

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newPassword, $existingUser['id']]);

        $result = ['status' => 'success'];

    } elseif ($action === 'getUsers') {
        $stmt = $pdo->query("SELECT username, nama, role FROM users ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];

    } elseif ($action === 'addWarga') {
        $p = $payload;
        $username = trim($p['username'] ?? '');
        $password = trim($p['password'] ?? '');
        $no_kk = $p['no_kk'] ?? '';
        $nik = $p['nik'] ?? '';
        $nama = $p['nama'] ?? '';
        $jk = $p['jk'] ?? '';
        $kerja = $p['kerja'] ?? '';
        $hp = $p['hp'] ?? '';
        $alamat = $p['alamat'] ?? '';

        if ($username === '' || $password === '') {
            throw new Exception('Username dan password wajib diisi untuk warga baru.');
        }

        $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmtCheckUser->execute([$username]);
        if ($stmtCheckUser->fetch()) {
            throw new Exception('Username sudah digunakan. Silakan pilih username lain.');
        }

        $stmt = $pdo->prepare("INSERT INTO warga (username, password, no_kk, nik, nama, jk, kerja, hp, alamat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $no_kk, $nik, $nama, $jk, $kerja, $hp, $alamat]);
        $wargaId = $pdo->lastInsertId();

        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, 'warga')");
        $stmtUser->execute([$username, $password, $nama]);

        // Buat tagihan iuran otomatis untuk warga baru agar muncul di Manajemen Iuran
        $bulanIuran = date('m-Y');
        $nominalIuran = 76000;
        $statusIuran = 'Belum Lunas';
        $stmtIuran = $pdo->prepare("INSERT INTO iuran (id_warga, user, nama, bulan, nominal, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIuran->execute([$wargaId, $username, $nama, $bulanIuran, $nominalIuran, $statusIuran]);

        $result = ['status' => 'success', 'data' => ['id' => $wargaId]];

    } elseif ($action === 'getKeluhan') {
        $stmt = $pdo->query("SELECT * FROM keluhan ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];

        } elseif ($action === 'addKeluhan') {
            $p = $payload;
            $tgl = $p['tgl'] ?? date('d/m/Y');
            $user = $p['user'] ?? '';
            $nama = $p['nama'] ?? '';
            $kategori = $p['kategori'] ?? '';
            $desc = $p['desc'] ?? ($p['deskripsi'] ?? '');
            $foto = $p['foto'] ?? null; // Base64 encoded image atau null
            $status = 'Menunggu';
            $tanggapan = '-';
            $stmt = $pdo->prepare("INSERT INTO keluhan (tgl, user, nama, kategori, deskripsi, foto, status, tanggapan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tgl, $user, $nama, $kategori, $desc, $foto, $status, $tanggapan]);
            $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

        } elseif ($action === 'updateKeluhan') {
            $p = $payload;
            $id = $p['id'] ?? null;
            $status = $p['status'] ?? null;
            $tanggapan = $p['tanggapan'] ?? null;
            $foto_admin = $p['foto_admin'] ?? null;
            if ($id === null) throw new Exception('Missing id');
            
            if ($foto_admin !== null) {
                // Update dengan foto admin
                $stmt = $pdo->prepare("UPDATE keluhan SET status = ?, tanggapan = ?, foto_admin = ? WHERE id = ?");
                $stmt->execute([$status, $tanggapan, $foto_admin, $id]);
            } else {
                // Update tanpa foto admin
                $stmt = $pdo->prepare("UPDATE keluhan SET status = ?, tanggapan = ? WHERE id = ?");
                $stmt->execute([$status, $tanggapan, $id]);
            }
            $result = ['status' => 'success'];

        } elseif ($action === 'delKeluhan') {
            $p = $payload;
            $id = $p['id'] ?? null;
            if ($id === null) throw new Exception('Missing id');
            $stmt = $pdo->prepare("DELETE FROM keluhan WHERE id = ?");
            $stmt->execute([$id]);
            $result = ['status' => 'success'];

    } elseif ($action === 'getSurat') {
        $stmt = $pdo->query("SELECT * FROM surat ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];
    } elseif ($action === 'addSurat') {
        $p = $payload;
        $tgl = $p['tgl'] ?? date('d/m/Y');
        $user = $p['user'] ?? '';
        $nama = $p['nama'] ?? '';
        $jenis = $p['jenis'] ?? '';
        $ket = $p['ket'] ?? '';
        $status = 'Menunggu';
        $stmt = $pdo->prepare("INSERT INTO surat (tgl, user, nama, jenis, ket, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tgl, $user, $nama, $jenis, $ket, $status]);
        $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

    } elseif ($action === 'updateSurat') {
        $p = $payload;
        $id = $p['id'] ?? null;
        $status = $p['status'] ?? null;
        if ($id === null) throw new Exception('Missing id');
        $stmt = $pdo->prepare("UPDATE surat SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $result = ['status' => 'success'];

    } elseif ($action === 'getKeuangan') {
        $stmt = $pdo->query("SELECT * FROM keuangan ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['status' => 'success', 'data' => $rows];

    } elseif ($action === 'getIuran') {
        $stmt = $pdo->query("SELECT * FROM iuran ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $seen = [];
        $unique = [];
        foreach ($rows as $row) {
            $key = $row['user'] . '|' . $row['bulan'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $row;
            }
        }
        $result = ['status' => 'success', 'data' => $unique];

    } elseif ($action === 'addTrx') {
        $p = $payload;
        $tgl = $p['tgl'] ?? date('d/m/Y');
        $jenis = $p['jenis'] ?? '';
        $kategori = $p['kategori'] ?? '';
        $ket = $p['ket'] ?? '';
        $masuk = intval($p['masuk'] ?? 0);
        $keluar = intval($p['keluar'] ?? 0);
        $stmt = $pdo->prepare("INSERT INTO keuangan (tgl, jenis, kategori, ket, masuk, keluar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tgl, $jenis, $kategori, $ket, $masuk, $keluar]);
        $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

    } elseif ($action === 'addIuran') {
        $p = $payload;
        $user = $p['user'] ?? '';
        $nama = $p['nama'] ?? '';
        $bulan = $p['bulan'] ?? '';
        $nominal = intval($p['nominal'] ?? 0);
        $status = $p['status'] ?? 'Belum Lunas';
        $stmt = $pdo->prepare("INSERT INTO iuran (user, nama, bulan, nominal, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user, $nama, $bulan, $nominal, $status]);
        $result = ['status' => 'success', 'data' => ['id' => $pdo->lastInsertId()]];

    } elseif ($action === 'updateIuran') {
        $p = $payload;
        $id = $p['id'] ?? null;
        if ($id === null) throw new Exception('Missing id');
        // Tandai iuran sebagai Lunas
        $stmt = $pdo->prepare("UPDATE iuran SET status = 'Lunas' WHERE id = ?");
        $stmt->execute([$id]);

        // Ambil data iuran untuk membuat entri buku kas (keuangan)
        $stmtI = $pdo->prepare("SELECT * FROM iuran WHERE id = ? LIMIT 1");
        $stmtI->execute([$id]);
        $iuran = $stmtI->fetch(PDO::FETCH_ASSOC);
        if ($iuran) {
            $bulan = $iuran['bulan'];
            $nama = $iuran['nama'];
            $nominal = intval($iuran['nominal']);
            $ket = "Iuran $bulan - $nama";

            // Cegah duplikasi: jika sudah ada transaksi yang persis sama, jangan masukkan lagi
            $chk = $pdo->prepare("SELECT id FROM keuangan WHERE kategori = ? AND ket = ? AND masuk = ? LIMIT 1");
            $chk->execute(['Iuran Warga', $ket, $nominal]);
            if (!$chk->fetch()) {
                $tgl = date('d/m/Y');
                $ins = $pdo->prepare("INSERT INTO keuangan (tgl, jenis, kategori, ket, masuk, keluar) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$tgl, 'Pemasukan', 'Iuran Warga', $ket, $nominal, 0]);
            }
        }

        $result = ['status' => 'success'];

    } elseif ($action === 'delIuran') {
        $p = $payload;
        $id = $p['id'] ?? null;
        if ($id === null) throw new Exception('Missing id');
        $stmt = $pdo->prepare("DELETE FROM iuran WHERE id = ?");
        $stmt->execute([$id]);
        $result = ['status' => 'success'];

    } elseif ($action === 'delWarga') {
        $p = $payload;
        $id = $p['id'] ?? null;
        if ($id === null) throw new Exception('Missing id');
        $stmt = $pdo->prepare("DELETE FROM warga WHERE id = ?");
        $stmt->execute([$id]);
        $result = ['status' => 'success'];

    } elseif ($action === 'confirmPaymentIuran') {
        // Confirm payment submitted by warga (e.g., via WhatsApp/QRIS) and record to keuangan
        $p = $payload;
        $id = $p['id'] ?? null;
        if ($id === null) throw new Exception('Missing id');

        // Mark as Lunas
        $stmt = $pdo->prepare("UPDATE iuran SET status = 'Lunas' WHERE id = ?");
        $stmt->execute([$id]);

        // Insert to keuangan (avoid duplicates)
        $stmtI = $pdo->prepare("SELECT * FROM iuran WHERE id = ? LIMIT 1");
        $stmtI->execute([$id]);
        $iuran = $stmtI->fetch(PDO::FETCH_ASSOC);
        if ($iuran) {
            $bulan = $iuran['bulan'];
            $nama = $iuran['nama'];
            $nominal = intval($iuran['nominal']);
            $ket = "Iuran $bulan - $nama";

            $chk = $pdo->prepare("SELECT id FROM keuangan WHERE kategori = ? AND ket = ? AND masuk = ? LIMIT 1");
            $chk->execute(['Iuran Warga', $ket, $nominal]);
            if (!$chk->fetch()) {
                $tgl = date('d/m/Y');
                $ins = $pdo->prepare("INSERT INTO keuangan (tgl, jenis, kategori, ket, masuk, keluar) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$tgl, 'Pemasukan', 'Iuran Warga', $ket, $nominal, 0]);
            }
        }

        $result = ['status' => 'success'];

    } elseif ($action === 'updateUserProfile') {
        // Update user profile including photo
        $p = $payload;
        $username = trim($p['username'] ?? '');
        $nama = $p['nama'] ?? '';
        $email = $p['email'] ?? '';
        $hp = $p['hp'] ?? '';
        $alamat = $p['alamat'] ?? '';
        $foto = $p['foto'] ?? null; // Base64 encoded image atau null
        
        if ($username === '') {
            throw new Exception('Username wajib diisi.');
        }
        
        // Update user data
        if ($foto !== null) {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, hp = ?, alamat = ?, foto = ? WHERE username = ?");
            $stmt->execute([$nama, $email, $hp, $alamat, $foto, $username]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, hp = ?, alamat = ? WHERE username = ?");
            $stmt->execute([$nama, $email, $hp, $alamat, $username]);
        }
        
        $result = ['status' => 'success'];

    } else {
        $result = ['status' => 'error', 'message' => 'Aksi tidak dikenali: ' . $action];
    }
} catch (Exception $e) {
    // Tangkap error jika ada query yang gagal
    $result = ['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
}

// 4. Kembalikan Output JSON (setara dengan ContentService di GAS)
echo json_encode($result);