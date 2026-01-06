<?php
include 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? '';

if (!$userId) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

// Security: Sanitize User ID to prevent traversal
$userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
$userDir = STORAGE_DIR . $userId . '/';

if (!is_dir($userDir)) {
    mkdir($userDir, 0777, true);
}

switch ($action) {
    case 'save_chat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        $chatId = $_POST['chat_id'] ?? uniqid();
        $title = $_POST['title'] ?? 'New Chat';
        $content = $_POST['content'] ?? '[]'; // JSON string of messages
        
        $file = $userDir . $chatId . '.json';
        $data = [
            'id' => $chatId,
            'title' => $title,
            'updated_at' => time(),
            'messages' => json_decode($content)
        ];
        
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'chat_id' => $chatId]);
        break;

    case 'list_history':
        $files = glob($userDir . '*.json');
        $history = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $history[] = [
                'id' => $data['id'] ?? basename($file, '.json'),
                'title' => $data['title'] ?? 'Untitled',
                'updated_at' => $data['updated_at'] ?? filemtime($file)
            ];
        }
        // Sort by newest
        usort($history, function($a, $b) { return $b['updated_at'] - $a['updated_at']; });
        echo json_encode(['history' => $history]);
        break;

    case 'get_chat':
        $chatId = $_GET['chat_id'] ?? '';
        $chatId = preg_replace('/[^a-zA-Z0-9_-]/', '', $chatId);
        $file = $userDir . $chatId . '.json';
        
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            echo json_encode(['error' => 'Chat not found']);
        }
        break;
        
    case 'delete_chat':
        $chatId = $_POST['chat_id'] ?? '';
        $chatId = preg_replace('/[^a-zA-Z0-9_-]/', '', $chatId);
        $file = $userDir . $chatId . '.json';
        if (file_exists($file)) {
            unlink($file);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'File not found']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
