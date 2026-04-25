<?php
set_time_limit(3600); 
ini_set('memory_limit', '1024M');

require_once "config/cors.php";
require_once "config/database.php";

$brand = isset($_GET['brand']) ? strtoupper($_GET['brand']) : 'PW';
$valid_brands = ['PW', 'NT'];
if (!in_array($brand, $valid_brands)) $brand = 'PW';

$database = new Database();
$db = $database->getConnection();

$stats = ['batches'=>0, 'subjects'=>0, 'videos'=>0, 'notes'=>0];

// --- Fetcher ---
function fetch_html($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 35,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return $body ?: '';
}

// --- Database Statements ---
$batch_ins = $db->prepare("INSERT INTO batches (external_id, name, banner_url, brand) VALUES (:eid, :name, :banner, :brand) ON DUPLICATE KEY UPDATE name=:name, banner_url=:banner, brand=:brand");
$subj_ins  = $db->prepare("INSERT INTO subjects (external_id, name, batch_id) VALUES (:eid, :name, :bid) ON DUPLICATE KEY UPDATE name=:name");
$vid_ins   = $db->prepare("INSERT INTO videos (external_id, title, video_url, thumbnail_url, topic_id, subject_id) VALUES (:eid, :title, :url, :thumb, :tid, :sid) ON DUPLICATE KEY UPDATE title=:title, video_url=:url, thumbnail_url=:thumb");
$note_ins  = $db->prepare("INSERT INTO notes (external_id, title, file_url, topic_id, subject_id) VALUES (:eid, :title, :url, :tid, :sid) ON DUPLICATE KEY UPDATE title=:title, file_url=:url");

// --- NT Scraper ---
function sync_nt_folder($batch_id, $folder_id, $folder_name, $batch_db_id, $parent_sid = null) {
    global $db, $subj_ins, $vid_ins, $note_ins, $stats;

    $url = "https://rolexcoderz.in/NT/content/index.php?course_id={$batch_id}&folder_id={$folder_id}&folder_title=".urlencode($folder_name)."&tab=content";
    $html = fetch_html($url);

    // 1. Folders -> Subjects
    preg_match_all('/folder_id=(\d+)[^"]*folder_title=([^&"]+)/is', $html, $f_matches, PREG_SET_ORDER);
    foreach ($f_matches as $fm) {
        $sub_id = $fm[1];
        $sub_name = trim(urldecode($fm[2]));
        $target_sid = $parent_sid;

        if (!$target_sid) {
            $subj_ins->execute([':eid' => $sub_id, ':name' => $sub_name, ':bid' => $batch_db_id]);
            $target_sid = $db->query("SELECT id FROM subjects WHERE external_id='$sub_id' LIMIT 1")->fetchColumn();
            $stats['subjects']++;
        }
        sync_nt_folder($batch_id, $sub_id, $sub_name, $batch_db_id, $target_sid);
    }

    // 2. Videos/Notes
    preg_match_all('/class="video-item"([\s\S]*?)<\/div>/is', $html, $v_blocks);
    foreach ($v_blocks[1] as $block) {
        preg_match('/data-id="([^"]+)"/is', $block, $id_m);
        preg_match('/data-pdf="([^"]+)"/is', $block, $pdf_m);
        preg_match('/class="vi-name">([^<]+)/is', $block, $ttl_m);
        
        $v_id = $id_m[1] ?? '';
        $v_is_pdf = $pdf_m[1] ?? '0';
        $v_title = trim($ttl_m[1] ?? 'Lecture');

        if ($v_id && $parent_sid) {
            if ($v_is_pdf == "1") {
                $note_ins->execute([':eid'=>$v_id, ':title'=>$v_title, ':url'=>'', ':tid'=>0, ':sid'=>$parent_sid]);
                $stats['notes']++;
            } else {
                $vid_ins->execute([':eid'=>$v_id, ':title'=>$v_title, ':url'=>'', ':thumb'=>'', ':tid'=>0, ':sid'=>$parent_sid]);
                $stats['videos']++;
            }
        }
    }
}

try {
    $brand_url = "https://rolexcoderz.in/{$brand}/";
    $brand_html = fetch_html($brand_url);
    preg_match_all('/href="content\/index\.php\?course_id=(\d+)".*?<img src="([^"]+)".*?<div class="card-name">([^<]+)<\/div>/is', $brand_html, $batches, PREG_SET_ORDER);

    foreach ($batches as $b) {
        $ext_id = $b[1]; $banner = $b[2]; $name = trim(html_entity_decode($b[3]));
        $batch_ins->execute([':eid'=>$ext_id, ':name'=>$name, ':banner'=>$banner, ':brand'=>$brand]);
        $batch_db_id = $db->query("SELECT id FROM batches WHERE external_id='$ext_id' AND brand='$brand' LIMIT 1")->fetchColumn();
        sync_nt_folder($ext_id, 0, 'Root', $batch_db_id);
        $stats['batches']++;
    }

    echo json_encode(["status" => "success", "brand" => $brand, "stats" => $stats]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
