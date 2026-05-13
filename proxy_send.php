<?php
// proxy_sms.php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit(); 
}

// Get the raw POST data from the JavaScript fetch
$inputData = file_get_contents('php://input');

// This is the target URL on your remote server
$targetUrl = 'https://whats.asbfashion.com/send_sms_batch.php';

$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(["success" => false, "error" => "Curl Error: " . curl_error($ch)]);
} else {
    // Forward the response and code from the remote server to your JS
    http_response_code($httpCode);
    echo $response;
}
curl_close($ch);