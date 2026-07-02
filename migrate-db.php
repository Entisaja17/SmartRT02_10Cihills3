<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load env
function loadEnvFile($filePath) {
    if (!is_readable($filePath)) return;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key && $value) putenv("$key=$value");
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'db_smart_rt';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("USE `$db`");
    
    echo "✅ Terhubung ke database '$db'\n\n";
    
    // Update warga table - tambah username dan password kolom
    echo "🔄 Mengupdate table warga...\n";
    try {
        $checkUsername = $pdo->query("SHOW COLUMNS FROM warga LIKE 'username'")->fetch();
        if (!$checkUsername) {
            $pdo->exec("ALTER TABLE warga ADD COLUMN username VARCHAR(100) DEFAULT NULL");
            echo "   ✅ Kolom 'username' ditambahkan ke warga\n";
        } else {
            echo "   ℹ️  Kolom 'username' sudah ada di warga\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $checkPassword = $pdo->query("SHOW COLUMNS FROM warga LIKE 'password'")->fetch();
        if (!$checkPassword) {
            $pdo->exec("ALTER TABLE warga ADD COLUMN password VARCHAR(255) DEFAULT NULL");
            echo "   ✅ Kolom 'password' ditambahkan ke warga\n";
        } else {
            echo "   ℹ️  Kolom 'password' sudah ada di warga\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Update iuran table - tambah id_warga kolom
    echo "\n🔄 Mengupdate table iuran...\n";
    try {
        $checkIdWarga = $pdo->query("SHOW COLUMNS FROM iuran LIKE 'id_warga'")->fetch();
        if (!$checkIdWarga) {
            $pdo->exec("ALTER TABLE iuran ADD COLUMN id_warga INT DEFAULT NULL AFTER id");
            echo "   ✅ Kolom 'id_warga' ditambahkan ke iuran\n";
        } else {
            echo "   ℹ️  Kolom 'id_warga' sudah ada di iuran\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Verify structure
    echo "\n📋 Verifikasi struktur table:\n";
    
    echo "\n   Table warga:\n";
    $cols = $pdo->query("SHOW COLUMNS FROM warga")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "      - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n   Table iuran:\n";
    $cols = $pdo->query("SHOW COLUMNS FROM iuran")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "      - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n✅ Database schema berhasil diperbarui!\n";
    
} catch (PDOException $e) {
    echo "❌ Error koneksi: " . $e->getMessage() . "\n";
}
?>
