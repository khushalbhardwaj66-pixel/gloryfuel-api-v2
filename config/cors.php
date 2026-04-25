<?php
// Force Allow EVERYTHING
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Max-Age: 86400"); // Cache this for 24 hours

// Critical: Tell the browser it's definitely JSON
header("Content-Type: application/json; charset=UTF-8");

// Handle the "pre-flight" check (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
