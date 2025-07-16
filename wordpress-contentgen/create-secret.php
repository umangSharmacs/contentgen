<?php
/**
 * Script to manually create the webhook secret
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yourdomain.com/create-secret.php
 */

// Load WordPress
require_once('wp-config.php');

// Check if secret already exists
$existing_secret = get_option('contentgen_webhook_secret', '');

if (empty($existing_secret)) {
    // Create the secret
    $new_secret = '12345678'; // Simple secret for testing
    $created = add_option('contentgen_webhook_secret', $new_secret);
    
    if ($created) {
        echo "<h1>✅ Webhook Secret Created!</h1>";
        echo "<p><strong>New Secret:</strong> <code>" . $new_secret . "</code></p>";
    } else {
        echo "<h1>❌ Failed to Create Secret</h1>";
        echo "<p>Could not create the webhook secret.</p>";
    }
} else {
    // Update existing secret to simple one
    $updated = update_option('contentgen_webhook_secret', '12345678');
    
    if ($updated) {
        echo "<h1>✅ Webhook Secret Updated!</h1>";
        echo "<p><strong>New Secret:</strong> <code>12345678</code></p>";
    } else {
        echo "<h1>❌ Failed to Update Secret</h1>";
        echo "<p>Could not update the webhook secret.</p>";
    }
}

// Verify the secret
$current_secret = get_option('contentgen_webhook_secret', 'NOT_FOUND');
echo "<hr>";
echo "<p><strong>Current Secret in Database:</strong> <code>" . $current_secret . "</code></p>";

// Show webhook URL
echo "<p><strong>Webhook URL:</strong> <code>" . get_site_url() . "/wp-admin/admin-ajax.php?action=contentgen_webhook</code></p>";

// Show test command
echo "<hr>";
echo "<h2>Test Command:</h2>";
echo "<pre>";
echo "curl -X POST " . get_site_url() . "/wp-admin/admin-ajax.php?action=contentgen_webhook \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Webhook-Secret: 12345678\" \\\n";
echo "  -d '{\n";
echo "    \"pmid\": \"12345678\",\n";
echo "    \"tweet\": \"Test tweet\",\n";
echo "    \"cancerType\": \"Test Cancer\"\n";
echo "  }'";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Delete this file from your server</li>";
echo "<li>Test the webhook with the cURL command above</li>";
echo "<li>Use secret '12345678' in your n8n workflow</li>";
echo "</ol>";
?> 