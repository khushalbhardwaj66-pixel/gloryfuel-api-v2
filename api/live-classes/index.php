<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

$database = new Database();
$db = $database->getConnection();

$brand = isset($_GET['brand']) ? $_GET['brand'] : null;

if($brand) {
    $query = "SELECT * FROM live_classes WHERE brand = ? ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$brand]);
} else {
    $query = "SELECT * FROM live_classes ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
}

$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["status" => "success", "data" => $classes]);
?>