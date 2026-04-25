<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$brand = isset($_GET['brand']) ? $_GET['brand'] : 'NT';

$query = "SELECT * FROM batches WHERE brand = ? ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute([$brand]);

$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($batches);
?>
