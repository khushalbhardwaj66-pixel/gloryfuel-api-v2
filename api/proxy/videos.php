<?php
/**
 * VIDEO URL PROXY API
 * Returns all video URLs stored in the database.
 *
 * Endpoint: GET /gloryfuel-api/api/proxy/videos.php
 *
 * Optional query params:
 *   ?subject_id=5        — filter by subject ID
 *   ?batch_id=3          — filter by batch ID (via subjects join)
 *   ?topic_id=12         — filter by topic ID
 *   ?search=physics      — filter by title keyword
 *   ?page=1&limit=50     — pagination (default: no limit)
 *   ?fields=id,title,video_url — return only specific columns
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    // ── Parse query params ────────────────────────────────────────────────
    $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
    $batch_id   = isset($_GET['batch_id'])   ? (int)$_GET['batch_id']   : null;
    $topic_id   = isset($_GET['topic_id'])   ? (int)$_GET['topic_id']   : null;
    $search     = isset($_GET['search'])     ? trim($_GET['search'])     : null;
    $do_paging  = isset($_GET['page']) && isset($_GET['limit']);
    $page       = $do_paging ? max(1, (int)$_GET['page'])         : null;
    $limit      = $do_paging ? min(500, (int)$_GET['limit'])      : null;
    $fields_raw = isset($_GET['fields'])     ? trim($_GET['fields'])     : null;

    // ── WHERE clause builder ──────────────────────────────────────────────
    $where  = "WHERE 1=1";
    $params = [];

    if ($subject_id !== null) {
        $where .= " AND v.subject_id = :subject_id";
        $params[':subject_id'] = $subject_id;
    }
    if ($batch_id !== null) {
        $where .= " AND b.id = :batch_id";
        $params[':batch_id'] = $batch_id;
    }
    if ($topic_id !== null) {
        $where .= " AND v.topic_id = :topic_id";
        $params[':topic_id'] = $topic_id;
    }
    if (!empty($search)) {
        $where .= " AND v.title LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    // ── FROM + JOIN (shared) ──────────────────────────────────────────────
    $from_joins = "FROM videos v
                   LEFT JOIN subjects s ON v.subject_id = s.id
                   LEFT JOIN batches  b ON s.batch_id   = b.id
                   LEFT JOIN topics   t ON v.topic_id   = t.id";

    // ── Count total for pagination ────────────────────────────────────────
    $total = null;
    if ($do_paging) {
        $count_stmt = $db->prepare("SELECT COUNT(*) $from_joins $where");
        $count_stmt->execute($params);
        $total  = (int)$count_stmt->fetchColumn();
        $offset = ($page - 1) * $limit;
    }

    // ── Main data query ───────────────────────────────────────────────────
    $select = "SELECT 
                v.id,
                v.title,
                v.video_url,
                v.thumbnail_url,
                v.subject_id,
                v.topic_id,
                s.name   AS subject_name,
                b.id     AS batch_id,
                b.name   AS batch_name,
                t.name   AS topic_name,
                v.created_at";

    $order = "ORDER BY b.id ASC, s.id ASC, v.id ASC";

    $sql = "$select $from_joins $where $order";

    if ($do_paging) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    if ($do_paging) {
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Field filtering ───────────────────────────────────────────────────
    if (!empty($fields_raw)) {
        $allowed = array_flip(array_map('trim', explode(',', $fields_raw)));
        $videos  = array_map(fn($row) => array_intersect_key($row, $allowed), $videos);
    }

    // ── Response ──────────────────────────────────────────────────────────
    $response = [
        'status' => 'success',
        'count'  => count($videos),
    ];

    if ($do_paging) {
        $response['pagination'] = [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
        ];
    }

    $response['data'] = $videos;

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
?>
