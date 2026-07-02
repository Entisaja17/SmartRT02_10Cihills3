<?php
require_once __DIR__ . '/koneksi.php';

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('admin','peserta')");
    $stmt->execute(['123']);
    $count = $stmt->rowCount();
    echo "<h3>Update seed selesai.</h3>\n";
    echo "<p>Password untuk 'admin' dan 'peserta' diubah menjadi <strong>123</strong>.</p>\n";
    echo "<p>Baris terpengaruh: " . htmlentities($count) . "</p>\n";
    echo "<p><a href=\"/SmartRT02/\">Kembali ke aplikasi</a></p>\n";
} catch (Exception $e) {
    echo "<h3>Gagal memperbarui seed:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
