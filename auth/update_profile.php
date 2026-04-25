<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

$id = $data->id;

if (isset($data->action) && $data->action === 'change_password') {
    if (!isset($data->current_password) || !isset($data->new_password)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing password fields"]);
        exit();
    }

    // Verify current password
    $query = "SELECT password FROM users WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data->current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid current password"]);
        exit();
    }

    // Update to new password
    $new_password = password_hash($data->new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password = :password WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":password", $new_password);
    $update_stmt->bindParam(":id", $id);

    if ($update_stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Password updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Internal server error"]);
    }
} else {
    // Basic profile update (name, email)
    if (!isset($data->name) || !isset($data->email)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing name or email"]);
        exit();
    }

    $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":id", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Internal server error"]);
    }
}
?>