require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $db->prepare("UPDATE settings SET s_value = ? WHERE s_key = 'sync_schedule'");
    $stmt->execute([json_encode($data)]);
    echo json_encode(['status' => 'success']);
} else {
    $stmt = $db->prepare("SELECT s_value FROM settings WHERE s_key = 'sync_schedule'");
    $stmt->execute();
    echo $stmt->fetchColumn() ?: json_encode(['enabled'=>false, 'frequency'=>'daily', 'time'=>'00:00']);
}
