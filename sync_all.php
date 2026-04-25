<?php
set_time_limit(3600); 
ini_set('memory_limit', '1024M');

require_once "config/cors.php";
require_once "config/database.php";

$brand = isset($_GET['brand']) ? strtoupper($_GET['brand']) : 'PW';
$database = new Database();
$db = $database->getConnection();

$stats = ['batches'=>0, 'subjects'=>0, 'videos'=>0, 'notes'=>0];

function fetch_html($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 35,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["body" => $body ?: '', "code" => $http_code];
}

try {
    $brand_url = "https://rolexcoderz.in/{$brand}/";
    $result = fetch_html($brand_url);
    
    if ($result['code'] !== 200) {
        throw new Exception("Proxy returned error code: " . $result['code']);
    }

    preg_match_all('/href="content\/index\.php\?course_id=(\d+)".*?<img src="([^"]+)".*?<div class="card-name">([^<]+)<\/div>/is', $result['body'], $batches, PREG_SET_ORDER);

    if (empty($batches)) {
        throw new Exception("No batches found in the HTML. Proxy might be blocked or HTML changed.");
    }

    foreach ($batches as $b) {
        // Sync logic...
        $stats['batches']++;
    }

    echo json_encode(["status" => "success", "debug_code" => $result['code'], "stats" => $stats]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
