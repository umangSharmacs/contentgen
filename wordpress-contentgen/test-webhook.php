<?php
/**
 * Simple webhook test script
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yourdomain.com/test-webhook.php
 */

// Replace with your actual domain and secret
$domain = 'https://yourdomain.com'; // CHANGE THIS
$secret = '12345678';   // Simple secret for testing

// Test data
$testData = [
    'pmid' => '12345678',
    'date' => '2024-01-15',
    'journal' => 'Nature',
    'tweet' => 'Test tweet from PHP script',
    'doi' => '10.1038/test123',
    'cancerType' => 'Test Cancer',
    'summary' => 'This is a test summary',
    'abstract' => 'This is a test abstract',
    'twitterHashtags' => '#test #cancer',
    'twitterAccounts' => '@testuser',
    'score' => 0.85
];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Webhook-Secret: ' . $secret
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Display results
echo "<h1>Webhook Test Results</h1>";
echo "<p><strong>URL:</strong> " . $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook' . "</p>";
echo "<p><strong>Secret Used:</strong> " . $secret . "</p>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

if ($error) {
    echo "<p><strong>cURL Error:</strong> " . $error . "</p>";
} else {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Decode and display JSON response
$responseData = json_decode($response, true);
if ($responseData) {
    echo "<p><strong>Decoded Response:</strong></p>";
    echo "<pre>" . print_r($responseData, true) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Test Data Sent:</strong></p>";
echo "<pre>" . print_r($testData, true) . "</pre>";
?> 