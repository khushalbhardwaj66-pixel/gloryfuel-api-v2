<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

$database = new Database();
$db = $database->getConnection();

$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;

if($subject_id) {
    $query = "SELECT * FROM notes WHERE subject_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$subject_id]);
} else {
    $query = "SELECT * FROM notes ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
}

$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["status" => "success", "data" => $notes]);
?>