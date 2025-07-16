<?php
/**
 * Debug script to see what headers are being received
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yourdomain.com/debug-webhook.php
 */

// Load WordPress
require_once('wp-config.php');

echo "<h1>Webhook Debug Information</h1>";

// Show all headers
echo "<h2>All Headers Received:</h2>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo $key . ": " . $value . "\n";
    }
}
echo "</pre>";

// Show specific webhook secret
echo "<h2>Webhook Secret Check:</h2>";
$webhook_secret = get_option('contentgen_webhook_secret', 'NOT_FOUND');
echo "<p><strong>Stored Secret:</strong> <code>" . $webhook_secret . "</code></p>";

// Check different header formats
$header_variations = [
    'HTTP_WEBHOOK_SECRET' => $_SERVER['HTTP_WEBHOOK_SECRET'] ?? 'NOT_FOUND',
    'HTTP_WEBHOOK-SECRET' => $_SERVER['HTTP_WEBHOOK-SECRET'] ?? 'NOT_FOUND',
    'HTTP_X_WEBHOOK_SECRET' => $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? 'NOT_FOUND',
    'HTTP_X_WEBHOOK-SECRET' => $_SERVER['HTTP_X_WEBHOOK-SECRET'] ?? 'NOT_FOUND',
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT_FOUND'
];

echo "<h2>Header Variations:</h2>";
echo "<pre>";
foreach ($header_variations as $header => $value) {
    echo $header . ": " . $value . "\n";
}
echo "</pre>";

// Show the actual webhook handler logic
echo "<h2>Current Webhook Handler Logic:</h2>";
echo "<pre>";
echo "// Current code in handle_webhook():\n";
echo "\$webhook_secret = get_option('contentgen_webhook_secret', '');\n";
echo "if (!empty(\$webhook_secret)) {\n";
echo "    \$received_secret = \$_SERVER['HTTP_WEBHOOK_SECRET'] ?? '';\n";
echo "    if (\$received_secret !== \$webhook_secret) {\n";
echo "        wp_die('Unauthorized', 'Unauthorized', array('response' => 401));\n";
echo "    }\n";
echo "}\n";
echo "</pre>";

// Test the actual logic
echo "<h2>Test Current Logic:</h2>";
$webhook_secret = get_option('contentgen_webhook_secret', '');
$received_secret = $_SERVER['HTTP_WEBHOOK_SECRET'] ?? '';

echo "<p><strong>Stored Secret:</strong> <code>" . $webhook_secret . "</code></p>";
echo "<p><strong>Received Secret:</strong> <code>" . $received_secret . "</code></p>";
echo "<p><strong>Match:</strong> " . ($received_secret === $webhook_secret ? '✅ YES' : '❌ NO') . "</p>";

echo "<hr>";
echo "<p><strong>To test with cURL, use:</strong></p>";
echo "<pre>";
echo "curl -X POST " . get_site_url() . "/wp-admin/admin-ajax.php?action=contentgen_webhook \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Webhook-Secret: 12345678\" \\\n";
echo "  -d '{\"pmid\":\"12345678\",\"tweet\":\"Test\"}'";
echo "</pre>";
?> 