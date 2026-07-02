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

    echo "✅ Database server berhubung\n";

    // Cek database
    $databases = $pdo->query("SHOW DATABASES LIKE '$db'")->fetchAll();
    if ($databases) {
        echo "✅ Database '$db' ada\n";
        
        $pdo->exec("USE `$db`");
        
        // Cek table users
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if ($tables) {
            echo "✅ Table 'users' ada\n";
            
            // Cek kolom
            $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
            echo "📋 Struktur table users:\n";
            foreach ($columns as $col) {
                echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
            
            // Cek sample data
            $count = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
            echo "📊 Jumlah data di users: $count\n";
            
            if ($count > 0) {
                $users = $pdo->query("SELECT id, username, nama, role FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                echo "📝 Sample data:\n";
                foreach ($users as $u) {
                    echo "   - Username: {$u['username']}, Nama: {$u['nama']}, Role: {$u['role']}\n";
                }
            }
        } else {
            echo "❌ Table 'users' TIDAK ADA - Database belum di-setup!\n";
            echo "   Jalankan: http://localhost/SmartRT02-10Cihills3/setup-database.php\n";
        }
    } else {
        echo "❌ Database '$db' TIDAK ADA\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
