<?php
/**
 * Debug script for 500 error
 * This script will help identify what's causing the 500 error
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Replace with your actual domain
$domain = 'https://yourdomain.com'; // CHANGE THIS

// Simple test data
$testData = [
    'items' => [
        [
            'PMID' => 12345678,
            'Date ' => '2024-01-15',
            'Journal' => 'Test Journal',
            'Tweet' => 'Test tweet',
            'Tweet (Few shot learning)' => 'Test few shot tweet',
            'DOI' => '10.1234/test',
            'Specific Cancer type' => 'Test Cancer',
            'Summary' => 'Test summary',
            'Abstract' => 'Test abstract',
            'Twiter Hashtags' => '#test',
            'Twiiter accounts tagged' => '@testuser',
            'Score' => 0.85
        ]
    ]
];

echo "<h1>500 Error Debug Test</h1>";

// Test 1: Check if the endpoint exists
echo "<h2>Test 1: Endpoint Check</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Endpoint URL:</strong> " . $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook' . "</p>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 2: Send minimal data
echo "<h2>Test 2: Minimal Data Test</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'data']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
if ($error) {
    echo "<p><strong>cURL Error:</strong> " . $error . "</p>";
}
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 3: Send actual data
echo "<h2>Test 3: Actual Data Test</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
if ($error) {
    echo "<p><strong>cURL Error:</strong> " . $error . "</p>";
}
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 4: Check for log file
echo "<h2>Test 4: Log File Check</h2>";
$logFile = ABSPATH . 'webhook-debug.log';
if (file_exists($logFile)) {
    echo "<p><strong>Log file exists:</strong> Yes</p>";
    echo "<p><strong>Log file size:</strong> " . filesize($logFile) . " bytes</p>";
    echo "<p><strong>Last 10 lines:</strong></p>";
    echo "<pre>" . htmlspecialchars(implode('', array_slice(file($logFile), -10))) . "</pre>";
} else {
    echo "<p><strong>Log file exists:</strong> No</p>";
}

echo "<hr>";
echo "<p><strong>Test Data Sent:</strong></p>";
echo "<pre>" . print_r($testData, true) . "</pre>";
?> 