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
    
    echo "🔍 Debug Login Test:\n\n";
    
    $username = 'admin';
    $passwordInput = 'edudigital';
    
    echo "Input:\n";
    echo "  Username: $username\n";
    echo "  Password: $passwordInput\n\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo "Database Data:\n";
        echo "  ID: " . $userData['id'] . "\n";
        echo "  Username: " . $userData['username'] . "\n";
        echo "  Password (DB): " . $userData['password'] . "\n";
        echo "  Nama: " . $userData['nama'] . "\n";
        echo "  Role: " . $userData['role'] . "\n\n";
        
        echo "Comparison:\n";
        echo "  Input Password: '$passwordInput'\n";
        echo "  DB Password:    '" . $userData['password'] . "'\n";
        echo "  Match: " . ($userData['password'] === $passwordInput ? '✅ YES' : '❌ NO') . "\n";
        
        // Check lengths
        echo "\n  Input length: " . strlen($passwordInput) . "\n";
        echo "  DB length: " . strlen($userData['password']) . "\n";
        
        // Check hex
        echo "\n  Input hex: " . bin2hex($passwordInput) . "\n";
        echo "  DB hex: " . bin2hex($userData['password']) . "\n";
        
    } else {
        echo "❌ User not found!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
