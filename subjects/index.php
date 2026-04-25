<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : die();

$query = "SELECT * FROM subjects WHERE batch_id = ? ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute([$batch_id]);

$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subjects);
?>
