<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    echo "🔄 Reset Password untuk semua users...\n\n";
    
    // Update ALL users password to edudigital
    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute(['edudigital']);
    
    echo "✅ Password berhasil direset ke 'edudigital'\n\n";
    
    // Verify
    $users = $pdo->query("SELECT id, username, nama, password FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "📋 Data users setelah update:\n";
    foreach ($users as $u) {
        echo "   - Username: {$u['username']}, Password: {$u['password']}, Nama: {$u['nama']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
