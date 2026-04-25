<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

$database = new Database();
$db = $database->getConnection();

$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if($batch_id) {
    $query = "SELECT * FROM subjects WHERE batch_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$batch_id]);
} else {
    $query = "SELECT * FROM subjects ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
}

$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["status" => "success", "data" => $subjects]);
?>