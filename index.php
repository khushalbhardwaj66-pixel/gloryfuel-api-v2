<?php
header("Content-Type: application/json");
echo json_encode([
    "status" => "online",
    "message" => "GloryFuel API is live and connected to TiDB Cloud!",
    "timestamp" => date("Y-m-d H:i:s")
]);
?>
