<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// target API
$url = 'https://bj-tricks-ai.vercel.app/chat';

// Prepare POST data
$postData = $_POST;
$files = $_FILES;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Handle file uploads if necessary (forwarding files via curl is complex without specific formatting, 
// strictly forwarding text for simplicity unless multipart is constructed manually, 
// keeping it simple for this "web ai dev" context).
// If the original API expects standard multipart/form-data:

$fields = $postData;

if (!empty($files['file']['tmp_name'])) {
    $cfile = new CURLFile($files['file']['tmp_name'], $files['file']['type'], $files['file']['name']);
    $fields['file'] = $cfile;
}

curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    http_response_code($httpCode);
    echo $response;
}

curl_close($ch);
