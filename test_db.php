<?php
require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // Test: count users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Database is connected!",
        "users_count" => $result['total']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
}
?>
