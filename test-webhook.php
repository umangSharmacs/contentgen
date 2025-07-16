<?php
/**
 * Simple Webhook Test Script
 * Upload this to your WordPress site to test if the webhook endpoint is accessible
 */

// Test if we can reach the webhook endpoint
$webhook_url = $_GET['url'] ?? '';

if (empty($webhook_url)) {
    echo "<h2>Webhook URL Test</h2>";
    echo "<p>Add ?url=YOUR_WEBHOOK_URL to test</p>";
    echo "<p>Example: ?url=https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook</p>";
    exit;
}

echo "<h2>Testing Webhook URL: $webhook_url</h2>";

// Test 1: Basic connectivity
echo "<h3>Test 1: Basic Connectivity</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ Connection Error: $error</p>";
} else {
    echo "<p style='color: green;'>✅ Connection Successful (HTTP $http_code)</p>";
}

// Test 2: POST request with data
echo "<h3>Test 2: POST Request</h3>";
$test_data = array(
    'pmid' => '12345678',
    'date' => '2024-01-15',
    'journal' => 'Test Journal',
    'tweet' => 'Test tweet content...',
    'score' => 8.5
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ POST Error: $error</p>";
} else {
    echo "<p style='color: green;'>✅ POST Successful (HTTP $http_code)</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test 3: Check if WordPress is accessible
echo "<h3>Test 3: WordPress Accessibility</h3>";
$wp_url = str_replace('/wp-admin/admin-ajax.php?action=contentgen_webhook', '', $webhook_url);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $wp_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ WordPress Site Error: $error</p>";
} else {
    echo "<p style='color: green;'>✅ WordPress Site Accessible (HTTP $http_code)</p>";
}

echo "<h3>Debugging Tips:</h3>";
echo "<ul>";
echo "<li>Make sure your WordPress site is running</li>";
echo "<li>Check if the ContentGen plugin is activated</li>";
echo "<li>Verify the webhook URL is correct</li>";
echo "<li>For Local by Flywheel, make sure the site is started</li>";
echo "<li>For Bluehost, check if your domain is accessible</li>";
echo "</ul>";
?> 