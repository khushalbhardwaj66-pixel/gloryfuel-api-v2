<?php
require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

// --- AUTO-FIX: Create table if it doesn't exist ---
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    s_key VARCHAR(100) UNIQUE,
    s_value TEXT
)");

$stmt = $db->prepare("SELECT s_value FROM settings WHERE s_key = 'last_sync_meta'");
$stmt->execute();
$val = $stmt->fetchColumn();

echo $val ?: json_encode(['last_sync_at' => null, 'new_records' => [], 'total_records' => []]);
?>
