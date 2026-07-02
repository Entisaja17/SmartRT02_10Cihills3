<?php
/**
 * SETUP FIX - Perbaikan konfigurasi dan database SmartRT
 * Jalankan sekali: http://localhost/SmartRT02-10Cihills3/fix-setup.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Setup SmartRT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .subtitle { color: #7f8c8d; margin-bottom: 20px; }
        .step { background: #f9f9f9; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .step h3 { color: #2c3e50; margin-bottom: 10px; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .code { background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 10px 0; overflow-x: auto; }
        ul { margin-left: 20px; }
        li { margin: 8px 0; }
        .next-step { background: #e8f4f8; border-left-color: #3498db; padding: 15px; margin-top: 20px; border-radius: 4px; }
        a { color: #3498db; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SmartRT - Fix Setup</h1>
        <p class="subtitle">Memperbaiki konfigurasi API dan database</p>

        <?php
        // Load env config
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
        $db = getenv('DB_NAME') ?: 'db_smart_rt';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $port = getenv('DB_PORT') ?: '3306';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $allFixed = true;

        // Step 1: Cek config.js
        echo '<div class="step">';
        echo '<h3>✓ Step 1: Verifikasi config.js</h3>';
        $configFile = __DIR__ . '/config.js';
        if (file_exists($configFile)) {
            $configContent = file_get_contents($configFile);
            if (strpos($configContent, "const API_BASE_URL = '.'") !== false) {
                echo '<p class="success">✅ config.js sudah benar (API_BASE_URL = \'.\')</p>';
            } else {
                echo '<p class="error">⚠️  config.js mungkin perlu diperbaiki</p>';
                echo '<p>Konten saat ini:</p>';
                echo '<div class="code">' . htmlspecialchars($configContent) . '</div>';
                $allFixed = false;
            }
        } else {
            echo '<p class="error">❌ config.js tidak ditemukan!</p>';
            $allFixed = false;
        }
        echo '</div>';

        // Step 2: Connect to database
        echo '<div class="step">';
        echo '<h3>✓ Step 2: Koneksi Database</h3>';
        try {
            $dsn = "mysql:host=$host;port=$port;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE ${charset}_general_ci");
            $pdo->exec("USE `$db`");
            
            echo '<p class="success">✅ Koneksi ke database berhasil</p>';
            echo '<div class="code">Host: ' . htmlspecialchars($host) . ' | DB: ' . htmlspecialchars($db) . '</div>';
        } catch (PDOException $e) {
            echo '<p class="error">❌ Gagal koneksi database: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $allFixed = false;
            echo '</div>';
            exit;
        }
        echo '</div>';

        // Step 3: Check and add columns to users table
        echo '<div class="step">';
        echo '<h3>✓ Step 3: Migrasi Users Table</h3>';
        try {
            $columnsNeeded = ['email', 'hp', 'alamat', 'foto'];
            $columnsMissing = [];
            
            foreach ($columnsNeeded as $colName) {
                $result = $pdo->query("SHOW COLUMNS FROM users LIKE '$colName'")->fetch();
                if (!$result) {
                    $columnsMissing[] = $colName;
                }
            }
            
            if (empty($columnsMissing)) {
                echo '<p class="success">✅ Semua kolom sudah ada di users table</p>';
            } else {
                echo '<p>Menambahkan kolom yang hilang: ' . implode(', ', $columnsMissing) . '</p>';
                
                if (in_array('email', $columnsMissing)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
                    echo '<p class="success">✅ Kolom email ditambahkan</p>';
                }
                if (in_array('hp', $columnsMissing)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN hp VARCHAR(50) DEFAULT NULL");
                    echo '<p class="success">✅ Kolom hp ditambahkan</p>';
                }
                if (in_array('alamat', $columnsMissing)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN alamat TEXT DEFAULT NULL");
                    echo '<p class="success">✅ Kolom alamat ditambahkan</p>';
                }
                if (in_array('foto', $columnsMissing)) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN foto LONGTEXT DEFAULT NULL");
                    echo '<p class="success">✅ Kolom foto ditambahkan</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $allFixed = false;
        }
        echo '</div>';

        // Step 4: Check test users
        echo '<div class="step">';
        echo '<h3>✓ Step 4: Verifikasi Test Users</h3>';
        try {
            $users = $pdo->query("SELECT username, password FROM users LIMIT 10")->fetchAll();
            if (empty($users)) {
                echo '<p class="warning">⚠️  Tidak ada user di database. Silakan jalankan setup-database.php terlebih dahulu.</p>';
                $allFixed = false;
            } else {
                echo '<p class="success">✅ Test users ditemukan:</p>';
                echo '<ul>';
                foreach ($users as $u) {
                    echo '<li>' . htmlspecialchars($u['username']) . ' (password: ' . htmlspecialchars($u['password']) . ')</li>';
                }
                echo '</ul>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $allFixed = false;
        }
        echo '</div>';

        // Step 5: Check API endpoint
        echo '<div class="step">';
        echo '<h3>✓ Step 5: Verifikasi API Endpoint</h3>';
        echo '<p>Cek apakah api.php dapat diakses:</p>';
        echo '<div class="code">GET ./api.php (Health Check)</div>';
        echo '<p>API harus mengembalikan: <code>{"status":"ok","message":"API siap"}</code></p>';
        echo '</div>';

        // Summary
        if ($allFixed) {
            echo '<div class="step success">';
            echo '<h3>✅ Semua perbaikan selesai!</h3>';
            echo '<p>Aplikasi SmartRT siap digunakan.</p>';
            echo '</div>';
            
            echo '<div class="next-step">';
            echo '<h3>🚀 Langkah Selanjutnya:</h3>';
            echo '<ol>';
            echo '<li><a href="index.html">Buka Aplikasi SmartRT</a></li>';
            echo '<li>Login dengan username: <strong>admin</strong>, password: <strong>edudigital</strong></li>';
            echo '<li>Atau login sebagai warga: <strong>peserta</strong>, password: <strong>edudigital</strong></li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="step error">';
            echo '<h3>⚠️  Ada beberapa masalah yang perlu diperbaiki</h3>';
            echo '<p>Silakan periksa error di atas dan jalankan kembali script ini.</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
