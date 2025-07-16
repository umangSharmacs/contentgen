<?php
/**
 * Bluehost API Capability Test
 * Upload this to your Bluehost public_html directory to test API functionality
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, webhook-secret');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log the request
    $log_entry = date('Y-m-d H:i:s') . " - Received POST request\n";
    $log_entry .= "Headers: " . json_encode(getallheaders()) . "\n";
    $log_entry .= "Body: " . $input . "\n";
    $log_entry .= "Data size: " . strlen($input) . " bytes\n";
    $log_entry .= "Memory usage: " . memory_get_usage(true) . " bytes\n";
    $log_entry .= "Max memory: " . ini_get('memory_limit') . "\n";
    $log_entry .= "Max execution time: " . ini_get('max_execution_time') . " seconds\n";
    $log_entry .= "----------------------------------------\n";
    
    // Write to log file
    file_put_contents('api_test.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    // Simulate processing time
    sleep(1);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint working',
        'received_data' => $data,
        'timestamp' => date('c'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return server information
    echo json_encode([
        'status' => 'API test endpoint active',
        'timestamp' => date('c'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars')
        ],
        'endpoints' => [
            'test_post' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'wordpress_webhook' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/wp-admin/admin-ajax.php?action=contentgen_webhook'
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 