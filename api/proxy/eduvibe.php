<?php
/**
 * EDUVIBE PROXY API
 * This acts as a secure proxy between your frontend and the EduvibeNT API
 * It handles authentication, decryption, and forwards content seamlessly
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ CONFIG ============
define('EDUVIBE_API', 'https://eduvibe-nt-api.wasmer.app');

// Load token from config
$config_file = '../../config/eduvibe_settings.json';
$saved_token = 'my_seceret_key_123';
if (file_exists($config_file)) {
    $conf = json_decode(file_get_contents($config_file), true);
    if (!empty($conf['token'])) {
        $saved_token = $conf['token'];
    }
}
define('EDUVIBE_TOKEN', $saved_token);
define('EDUVIBE_AES_KEY', 'eduvibe-auntypir');
define('EDUVIBE_AES_IV', 'eduvibe-4thclass');

// ============ HELPER FUNCTIONS ============

function decrypt_eduvibe($encrypted_base64)
{
    $cipher = base64_decode($encrypted_base64);
    $decrypted = openssl_decrypt(
        $cipher,
        'AES-128-CBC',
        EDUVIBE_AES_KEY,
        OPENSSL_RAW_DATA,
        EDUVIBE_AES_IV
    );
    return json_decode($decrypted, true);
}

function call_eduvibe_api($params)
{
    $url = EDUVIBE_API . '?' . http_build_query(array_merge([
        'token' => EDUVIBE_TOKEN
    ], $params));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        return null;
    }

    $json = json_decode($response, true);
    if (!isset($json['data'])) {
        return $json; // Return as-is if no encrypted data
    }

    return decrypt_eduvibe($json['data']);
}

// ============ MAIN PROXY LOGIC ============

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_course':
            // Get full course structure
            $course_id = $_GET['course_id'] ?? '39904';
            $data = call_eduvibe_api(['only_course_id' => $course_id]);

            if (!$data) {
                throw new Exception('Failed to fetch course data');
            }

            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'get_items':
            // Get items for a specific subject/topic
            $course_id = $_GET['course_id'] ?? '39904';
            $tile_id = $_GET['tile_id'] ?? '';
            $subject_id = $_GET['subject_id'] ?? '';
            $topic_id = $_GET['topic_id'] ?? '';
            $type = $_GET['type'] ?? 'video';
            $revert_api = $_GET['revert_api'] ?? '';

            if (empty($tile_id) || empty($subject_id) || empty($topic_id)) {
                throw new Exception('Missing required parameters');
            }

            $data = call_eduvibe_api([
                'course_id' => $course_id,
                'tile' => $tile_id,
                'subject' => $subject_id,
                'topic' => $topic_id,
                'layer' => 3,
                'type' => $type,
                'revert_api' => $revert_api
            ]);

            if (!$data) {
                throw new Exception('Failed to fetch items');
            }

            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'get_subjects':
            // Get all subjects for a course
            $course_id = $_GET['course_id'] ?? '39904';
            $data = call_eduvibe_api(['only_course_id' => $course_id]);

            if (!$data || !isset($data['data']['tiles'])) {
                throw new Exception('Failed to fetch subjects');
            }

            $tiles = $data['data']['tiles'];
            $subjects = [];

            foreach ($tiles as $tile) {
                if (isset($tile['meta']['list'])) {
                    foreach ($tile['meta']['list'] as $subject) {
                        $subjects[] = [
                            'id' => $subject['id'],
                            'name' => $subject['title'],
                            'thumbnail' => $subject['image_icon'] ?? '',
                            'tile_id' => $tile['id'],
                            'tile_type' => $tile['type']
                        ];
                    }
                }
            }

            // Remove duplicates
            $unique_subjects = [];
            $seen_ids = [];
            foreach ($subjects as $subject) {
                if (!in_array($subject['id'], $seen_ids)) {
                    $unique_subjects[] = $subject;
                    $seen_ids[] = $subject['id'];
                }
            }

            echo json_encode(['status' => 'success', 'data' => $unique_subjects]);
            break;

        case 'get_videos':
            // Get videos for a subject
            $course_id = $_GET['course_id'] ?? '39904';
            $subject_id = $_GET['subject_id'] ?? '';

            if (empty($subject_id)) {
                throw new Exception('Missing subject_id');
            }

            // First get the course structure
            $course_data = call_eduvibe_api(['only_course_id' => $course_id]);
            $tiles = $course_data['data']['tiles'] ?? [];

            $videos = [];

            // Find the Video tile
            foreach ($tiles as $tile) {
                if (strtolower($tile['type']) !== 'video')
                    continue;

                $subjects = $tile['meta']['list'] ?? [];
                foreach ($subjects as $subject) {
                    if ($subject['id'] !== $subject_id)
                        continue;

                    $topics = $subject['list'] ?? [];
                    foreach ($topics as $topic) {
                        $items = call_eduvibe_api([
                            'course_id' => $course_id,
                            'tile' => $tile['id'],
                            'subject' => $subject_id,
                            'topic' => $topic['id'],
                            'layer' => 3,
                            'type' => 'video',
                            'revert_api' => $tile['revert_api'] ?? ''
                        ]);

                        if ($items && isset($items['data']['list'])) {
                            $videos = array_merge($videos, $items['data']['list']);
                        }
                    }
                }
            }

            echo json_encode(['status' => 'success', 'data' => $videos]);
            break;

        case 'get_notes':
            // Get PDFs/notes for a subject
            $course_id = $_GET['course_id'] ?? '39904';
            $subject_id = $_GET['subject_id'] ?? '';

            if (empty($subject_id)) {
                throw new Exception('Missing subject_id');
            }

            $course_data = call_eduvibe_api(['only_course_id' => $course_id]);
            $tiles = $course_data['data']['tiles'] ?? [];

            $notes = [];

            // Find the Pdf tile
            foreach ($tiles as $tile) {
                if (strtolower($tile['type']) !== 'pdf')
                    continue;

                $subjects = $tile['meta']['list'] ?? [];
                foreach ($subjects as $subject) {
                    if ($subject['id'] !== $subject_id)
                        continue;

                    $topics = $subject['list'] ?? [];
                    foreach ($topics as $topic) {
                        $items = call_eduvibe_api([
                            'course_id' => $course_id,
                            'tile' => $tile['id'],
                            'subject' => $subject_id,
                            'topic' => $topic['id'],
                            'layer' => 3,
                            'type' => 'pdf',
                            'revert_api' => $tile['revert_api'] ?? ''
                        ]);

                        if ($items && isset($items['data']['list'])) {
                            $notes = array_merge($notes, $items['data']['list']);
                        }
                    }
                }
            }

            echo json_encode(['status' => 'success', 'data' => $notes]);
            break;

        default:
            throw new Exception('Invalid action. Available: get_course, get_items, get_subjects, get_videos, get_notes');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>