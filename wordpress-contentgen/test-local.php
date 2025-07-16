<?php
/**
 * Local by Flywheel Test Script for ContentGen Plugin
 * 
 * This script helps test the plugin functionality in Local by Flywheel
 * Upload this to your Local site's public_html directory
 */

// Prevent direct access if not in Local environment
if (!defined('ABSPATH') && !strpos($_SERVER['HTTP_HOST'], '.local')) {
    die('This script is for Local by Flywheel testing only');
}

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
    $log_entry = date('Y-m-d H:i:s') . " - Local Test: Received POST request\n";
    $log_entry .= "Headers: " . json_encode(getallheaders()) . "\n";
    $log_entry .= "Body: " . $input . "\n";
    $log_entry .= "Data size: " . strlen($input) . " bytes\n";
    $log_entry .= "Memory usage: " . memory_get_usage(true) . " bytes\n";
    $log_entry .= "Max memory: " . ini_get('memory_limit') . "\n";
    $log_entry .= "Max execution time: " . ini_get('max_execution_time') . " seconds\n";
    $log_entry .= "----------------------------------------\n";
    
    // Write to log file
    file_put_contents('local_test.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    // Simulate processing time
    sleep(1);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Local test endpoint working',
        'received_data' => $data,
        'timestamp' => date('c'),
        'environment' => 'Local by Flywheel',
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return server information and test instructions
    echo json_encode([
        'status' => 'Local by Flywheel Test Endpoint Active',
        'timestamp' => date('c'),
        'environment' => 'Local by Flywheel',
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ],
        'endpoints' => [
            'test_post' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'wordpress_webhook' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/wp-admin/admin-ajax.php?action=contentgen_webhook'
        ],
        'test_instructions' => [
            '1. Test this endpoint with POST request',
            '2. Check if WordPress plugin is installed',
            '3. Test WordPress webhook endpoint',
            '4. Verify database tables are created',
            '5. Test dashboard functionality'
        ],
        'sample_curl_command' => 'curl -X POST ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ' -H "Content-Type: application/json" -d \'{"test": "data"}\''
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 