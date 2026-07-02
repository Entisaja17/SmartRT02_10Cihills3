<?php
// Setup Database untuk SmartRT
// Jalankan script ini untuk inisialisasi database dari database.sql

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$db   = getenv('DB_NAME') ?: 'db_smart_rt';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database SmartRT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
        h1 { color: #2c3e50; margin-bottom: 20px; font-size: 24px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; border-radius: 4px; color: #2c3e50; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 4px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; border-radius: 4px; color: #721c24; }
        .config { background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px; }
        button { background: #3498db; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-size: 14px; margin: 10px 5px 10px 0; }
        button:hover { background: #2980b9; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        button:disabled { background: #bdc3c7; cursor: not-allowed; }
        .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .stat-box { background: #ecf0f1; padding: 15px; border-radius: 4px; text-align: center; }
        .stat-box .number { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .stat-box .label { color: #7f8c8d; font-size: 12px; margin-top: 5px; }
        .code-block { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; margin: 10px 0; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Setup Database SmartRT</h1>
        
        <div class="info">
            <strong>⚙️ Konfigurasi Database:</strong><br>
            <div class="config">
                Host: <?php echo htmlspecialchars($host); ?><br>
                Port: <?php echo htmlspecialchars($port); ?><br>
                Database: <?php echo htmlspecialchars($db); ?><br>
                User: <?php echo htmlspecialchars($user); ?><br>
                Charset: <?php echo htmlspecialchars($charset); ?>
            </div>
        </div>

        <?php
        try {
            // Connect to MySQL server (without selecting a database yet)
            $dsn = "mysql:host=$host;port=$port;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            echo '<div class="success">✅ Koneksi ke MySQL Server berhasil!</div>';

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE ${charset}_general_ci");
            echo '<div class="success">✅ Database <strong>' . htmlspecialchars($db) . '</strong> siap digunakan</div>';

            // Select the database
            $pdo->exec("USE `$db`");

            // Read and execute database.sql
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception('File database.sql tidak ditemukan di: ' . $sqlFile);
            }

            $sqlContent = file_get_contents($sqlFile);
            
            // Split SQL statements (simple parser)
            $statements = array_filter(
                array_map('trim', preg_split('/;[\s\n]*/', $sqlContent)),
                function($stmt) { return !empty($stmt); }
            );

            $executedCount = 0;
            $tableCreatedCount = 0;
            $insertedCount = 0;

            foreach ($statements as $statement) {
                if (empty(trim($statement))) {
                    continue;
                }
                
                try {
                    $pdo->exec($statement);
                    $executedCount++;
                    
                    // Count table creates and inserts
                    if (stripos($statement, 'CREATE TABLE') !== false) {
                        $tableCreatedCount++;
                    } elseif (stripos($statement, 'INSERT') !== false) {
                        $insertedCount++;
                    }
                } catch (Exception $e) {
                    // Some statements might fail if tables already exist, that's OK
                    if (stripos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }

            echo '<div class="success">✅ Semua SQL statements dijalankan dengan sukses!</div>';

            // Get table statistics
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $stats = [];
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`")->fetch();
                $stats[$table] = $count['cnt'];
            }

            echo '<div class="stats">';
            echo '<div class="stat-box"><div class="number">' . count($tables) . '</div><div class="label">Tabel Dibuat</div></div>';
            echo '<div class="stat-box"><div class="number">' . array_sum($stats) . '</div><div class="label">Total Data</div></div>';
            echo '</div>';

            echo '<div class="info"><strong>📊 Detail Tabel:</strong><br>';
            foreach ($stats as $table => $count) {
                echo htmlspecialchars($table) . ': <strong>' . $count . ' baris</strong><br>';
            }
            echo '</div>';

            // Test data verification
            $users = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch();
            $warga = $pdo->query("SELECT COUNT(*) as cnt FROM warga")->fetch();
            $pengumuman = $pdo->query("SELECT COUNT(*) as cnt FROM pengumuman")->fetch();
            $keluhan = $pdo->query("SELECT COUNT(*) as cnt FROM keluhan")->fetch();
            $iuran = $pdo->query("SELECT COUNT(*) as cnt FROM iuran")->fetch();

            echo '<div class="success">';
            echo '<strong>✅ Setup Selesai! Database siap digunakan.</strong><br><br>';
            echo 'Akun Login yang tersedia:<br>';
            echo '• Admin: <code>username: admin, password: edudigital</code><br>';
            echo '• Warga: <code>username: peserta, password: edudigital</code><br><br>';
            echo '<strong>Langkah selanjutnya:</strong><br>';
            echo '1. Buka aplikasi di: <a href="/">Aplikasi SmartRT</a><br>';
            echo '2. Login dengan akun di atas<br>';
            echo '3. Gunakan aplikasi secara normal';
            echo '</div>';

            // Add option to reset database
            echo '<br><form method="POST" style="margin-top: 20px;">';
            echo '<button type="submit" name="reset" value="yes" class="danger" onclick="return confirm(\'Yakin ingin reset database? Semua data akan dihapus!\')">🔄 Reset Database</button>';
            echo '</form>';

        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>💡 Solusi:</strong><br>';
            echo '1. Pastikan MySQL server sudah berjalan<br>';
            echo '2. Periksa konfigurasi di file <code>.env</code><br>';
            echo '3. Pastikan user MySQL memiliki privilege untuk membuat database<br>';
            echo '4. Refresh halaman untuk mencoba lagi';
            echo '</div>';
        }

        // Handle reset
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
            try {
                $dsn = "mysql:host=$host;port=$port;charset=$charset";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                
                $pdo->exec("DROP DATABASE IF EXISTS `$db`");
                
                echo '<div class="success">';
                echo '<strong>✅ Database berhasil direset!</strong><br>';
                echo 'Database akan dibuat ulang saat halaman di-refresh.';
                echo '</div>';
                
                // Redirect to refresh
                header('Refresh: 2; url=' . $_SERVER['REQUEST_URI']);
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>❌ Gagal reset database:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
