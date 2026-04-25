require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT s_value FROM settings WHERE s_key = 'last_sync_meta'");
$stmt->execute();
$val = $stmt->fetchColumn();

echo $val ?: json_encode(['last_sync_at' => null, 'new_records' => [], 'total_records' => []]);
