<?php
// Universal Sync Engine
require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$apiKey = getenv('ROLEX_API_KEY') ?: "YOUR_FALLBACK_KEY";
$apiBase = "https://rolexcoderz.in/PY";

$response = ["status" => "success", "batches_synced" => 0, "errors" => []];

function api_fetch($url, $key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "?api_key=" . $key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

try {
    // 1. Sync NextToppers (NT)
    $nt_data = api_fetch($apiBase . "/nt/batches", $apiKey);
    if (isset($nt_data['data'])) {
        foreach ($nt_data['data'] as $b) {
            $stmt = $db->prepare("INSERT INTO batches (external_id, name, banner_url, brand) VALUES (?, ?, ?, 'NT') ON DUPLICATE KEY UPDATE name=VALUES(name), banner_url=VALUES(banner_url)");
            $stmt->execute([$b['id'], $b['name'], $b['image']]);
            $response['batches_synced']++;
        }
    }

    // 2. Sync Physics Wallah (PW)
    $pw_data = api_fetch($apiBase . "/pw/batches", $apiKey);
    if (isset($pw_data['data'])) {
        foreach ($pw_data['data'] as $b) {
            $stmt = $db->prepare("INSERT INTO batches (external_id, name, banner_url, brand) VALUES (?, ?, ?, 'PW') ON DUPLICATE KEY UPDATE name=VALUES(name), banner_url=VALUES(banner_url)");
            $stmt->execute([$b['id'], $b['name'], $b['image']]);
            $response['batches_synced']++;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
