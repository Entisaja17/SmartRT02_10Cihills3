<?php
/**
 * Migration script: Add photo and profile fields to users table
 * Jalankan di browser: http://localhost/SmartRT02-10Cihills3/migrate-users.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database config
$host = 'localhost';
$db = 'db_smart_rt';
$user = 'root';
$pass = '';
$port = '3306';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h2>📋 Migration: Add Columns to users Table</h2>";
    echo "<hr>";
    
    $columnsMissing = [];
    
    // Check for each column
    $columnsNeeded = ['email', 'hp', 'alamat', 'foto'];
    foreach ($columnsNeeded as $colName) {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE '$colName'")->fetch();
        if (!$result) {
            $columnsMissing[] = $colName;
        }
    }
    
    if (empty($columnsMissing)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Semua kolom sudah ada!</p>";
    } else {
        echo "<p>Menambahkan kolom yang hilang:</p>";
        echo "<ul>";
        
        // Add email
        if (in_array('email', $columnsMissing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
            echo "<li>✅ Kolom <code>email</code> ditambahkan</li>";
        }
        
        // Add hp
        if (in_array('hp', $columnsMissing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN hp VARCHAR(50) DEFAULT NULL");
            echo "<li>✅ Kolom <code>hp</code> ditambahkan</li>";
        }
        
        // Add alamat
        if (in_array('alamat', $columnsMissing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN alamat TEXT DEFAULT NULL");
            echo "<li>✅ Kolom <code>alamat</code> ditambahkan</li>";
        }
        
        // Add foto
        if (in_array('foto', $columnsMissing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN foto LONGTEXT DEFAULT NULL");
            echo "<li>✅ Kolom <code>foto</code> ditambahkan</li>";
        }
        
        echo "</ul>";
        echo "<p style='color: green; font-weight: bold;'>✅ Migrasi selesai!</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='index.html'>← Kembali ke aplikasi</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
