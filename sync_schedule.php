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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $db->prepare("INSERT INTO settings (s_key, s_value) VALUES ('sync_schedule', ?) ON DUPLICATE KEY UPDATE s_value = ?");
    $stmt->execute([json_encode($data), json_encode($data)]);
    echo json_encode(['status' => 'success']);
} else {
    $stmt = $db->prepare("SELECT s_value FROM settings WHERE s_key = 'sync_schedule'");
    $stmt->execute();
    $val = $stmt->fetchColumn();
    echo $val ?: json_encode(['enabled'=>false, 'frequency'=>'daily', 'time'=>'00:00']);
}
?>
