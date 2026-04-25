<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : die();

$query = "SELECT * FROM videos WHERE subject_id = ? ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute([$subject_id]);

$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($videos);
?>
