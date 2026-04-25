<?php
// api/videos/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT v.*, s.name as subject_name 
          FROM videos v 
          LEFT JOIN subjects s ON v.subject_id = s.subject_id 
          ORDER BY v.id DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>