<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$brand = "NT"; // NextToppers
$baseUrl = "https://nexttoppers.com";

// --- HELPERS ---
function fetch_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// --- SYNC LOGIC ---
$response = ["status" => "success", "message" => "Sync started", "details" => []];

try {
    // 1. Get Batches
    $html = fetch_html($baseUrl . "/batches");
    
    // Improved Regex for Batches
    preg_match_all('/href="\/batch-details\/([^"]+)".*?alt="([^"]+)".*?src="([^"]+)"/s', $html, $matches);

    foreach ($matches[1] as $index => $external_id) {
        $name = $matches[2][$index];
        $banner = $matches[3][$index];

        $stmt = $db->prepare("INSERT INTO batches (external_id, name, banner_url, brand) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE name=?, banner_url=?");
        $stmt->execute([$external_id, $name, $banner, $brand, $name, $banner]);
        
        $response["details"][] = "Synced Batch: $name";
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
