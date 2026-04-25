<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

$database = new Database();
$db = $database->getConnection();

$brand = isset($_GET['brand']) ? $_GET['brand'] : null;

if($brand) {
    $query = "SELECT * FROM batches WHERE brand = ? ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$brand]);
} else {
    $query = "SELECT * FROM batches ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
}

$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["status" => "success", "data" => $batches]);
?>