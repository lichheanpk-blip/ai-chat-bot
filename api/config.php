<?php
// Global Configuration
define('API_URL', 'https://bj-tricks-ai.vercel.app/chat');
define('STORAGE_DIR', __DIR__ . '/../data/');
define('MAX_HISTORY_FILES', 50);

// Ensure storage directory exists
if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0777, true);
}
?>
