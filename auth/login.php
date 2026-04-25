<?php
require_once "../config/cors.php";
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Support both 'email' (from frontend) and 'username'
$identifier = null;
if (!empty($data->email)) {
    $identifier = $data->email;
} elseif (!empty($data->username)) {
    $identifier = $data->username;
}

$password = !empty($data->password) ? $data->password : null;

if ($identifier && $password) {
    // Match by username OR email column
    $query = "SELECT * FROM users WHERE username = ? OR username = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && ($password === $user['password'])) {
        // Generate a simple token
        $token = base64_encode($user['id'] . ':' . $user['username'] . ':' . time());

        echo json_encode([
            "status"  => "success",
            "message" => "Login successful",
            "token"   => $token,
            "user"    => [
                "id"       => $user['id'],
                "username" => $user['username'],
                "role"     => $user['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Incomplete data"]);
}
?>
