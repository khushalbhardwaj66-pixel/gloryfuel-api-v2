<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // Count Students (Users with role 'student' or 'user')
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('student', 'user')");
    $students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count Batches
    $stmt = $db->query("SELECT COUNT(*) as count FROM batches");
    $batches = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // For Live Classes and Notes, check if tables exist first to avoid errors
    $classes = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM live_classes WHERE status != 'ended'");
        $classes = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) { /* Table might not exist yet */ }

    $notes = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM notes");
        $notes = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) { /* Table might not exist yet */ }

    echo json_encode([
        "students" => $students,
        "batches" => (int)$batches,
        "classes" => (int)$classes,
        "notes" => (int)$notes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Dashboard Stats Error: " . $e->getMessage()]);
}
