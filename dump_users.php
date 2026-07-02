<?php
require_once __DIR__ . '/koneksi.php';
$pdo = get_db();
$stmt = $pdo->query('SELECT id, username, password, nama, role FROM users');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
