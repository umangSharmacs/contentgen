<?php
/**
 * Script to set a simple webhook secret
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yourdomain.com/set-simple-secret.php
 * 
 * WARNING: This sets a simple secret for testing only. Use a strong secret in production!
 */

// Load WordPress
require_once('wp-config.php');

// Simple secret for testing
$simple_secret = '12345678';

// Update the webhook secret in WordPress options
$updated = update_option('contentgen_webhook_secret', $simple_secret);

if ($updated) {
    echo "<h1>✅ Secret Updated Successfully!</h1>";
    echo "<p><strong>New Webhook Secret:</strong> <code>" . $simple_secret . "</code></p>";
    echo "<p><strong>Webhook URL:</strong> <code>" . get_site_url() . "/wp-admin/admin-ajax.php?action=contentgen_webhook</code></p>";
    
    echo "<hr>";
    echo "<h2>Test with cURL:</h2>";
    echo "<pre>";
    echo "curl -X POST " . get_site_url() . "/wp-admin/admin-ajax.php?action=contentgen_webhook \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"Webhook-Secret: " . $simple_secret . "\" \\\n";
    echo "  -d '{\n";
    echo "    \"pmid\": \"12345678\",\n";
    echo "    \"tweet\": \"Test tweet with simple secret\",\n";
    echo "    \"cancerType\": \"Test Cancer\",\n";
    echo "    \"journal\": \"Test Journal\"\n";
    echo "  }'";
    echo "</pre>";
    
    echo "<hr>";
    echo "<p><strong>⚠️ Security Warning:</strong> This simple secret is for testing only. Use a strong, random secret in production!</p>";
    
} else {
    echo "<h1>❌ Failed to Update Secret</h1>";
    echo "<p>Could not update the webhook secret. Please check your WordPress installation.</p>";
}

// Verify the secret was set
$current_secret = get_option('contentgen_webhook_secret');
echo "<hr>";
echo "<p><strong>Current Secret in Database:</strong> <code>" . $current_secret . "</code></p>";
?> 