<?php
// api/subjects/list.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Mock subjects if table empty or non-existent, otherwise fetch
try {
    $query = "SELECT * FROM subjects";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        // Return some defaults so the UI filters work
        $data = [
            ['id' => 1, 'name' => 'Mathemathics'],
            ['id' => 2, 'name' => 'Physics'],
            ['id' => 3, 'name' => 'Chemistry']
        ];
    }
} catch (Exception $e) {
    $data = [
        ['id' => 1, 'name' => 'General']
    ];
}

echo json_encode($data);
?>