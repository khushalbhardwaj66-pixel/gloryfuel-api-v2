<?php
// Smart Scraper Sync
require_once "config/cors.php";
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$response = ["status" => "success", "synced" => [], "count" => 0];

function get_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

try {
    // 1. Sync NextToppers Batches
    $html = get_content("https://nexttoppers.com/batches");
    
    // Look for batch IDs and Names in the HTML
    preg_match_all('/href="\/batch-details\/([^"]+)".*?alt="([^"]+)"/s', $html, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $index => $external_id) {
            $name = $matches[2][$index];
            
            $stmt = $db->prepare("INSERT INTO batches (external_id, name, brand) VALUES (?, ?, 'NT') ON DUPLICATE KEY UPDATE name=VALUES(name)");
            $stmt->execute([$external_id, $name]);
            
            $response['synced'][] = $name;
            $response['count']++;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
