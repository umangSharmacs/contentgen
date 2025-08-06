<?php
/**
 * Test file for N8N webhook
 * This file can be used to test if your N8N webhook is working correctly
 */

// Configuration
$n8n_webhook_url = 'YOUR_N8N_WEBHOOK_URL_HERE'; // Replace with your actual webhook URL

// Test data
$test_data = array(
    'title' => 'Test Article Title',
    'content' => 'This is a test article content. It should be enhanced by the AI system.',
    'timestamp' => time()
);

echo "Testing N8N Webhook...\n";
echo "URL: " . $n8n_webhook_url . "\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send request
$response = wp_remote_post($n8n_webhook_url, array(
    'headers' => array(
        'Content-Type' => 'application/json',
    ),
    'body' => json_encode($test_data),
    'timeout' => 30,
));

if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
    exit(1);
}

$response_code = wp_remote_retrieve_response_code($response);
$response_body = wp_remote_retrieve_body($response);

echo "Response Code: " . $response_code . "\n";
echo "Response Body:\n" . $response_body . "\n\n";

if ($response_code === 200) {
    echo "✅ Webhook test successful!\n";
    
    // Try to decode JSON response
    $decoded = json_decode($response_body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON response received\n";
            
            // Check for your specific N8N response format
            if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['enhanced_text'])) {
                echo "✅ Enhanced text found in response\n";
                echo "✅ Category: " . ($decoded[0]['category'] ?? 'Not set') . "\n";
                echo "✅ Subcategory: " . ($decoded[0]['subcategory'] ?? 'Not set') . "\n";
                echo "✅ Tags: " . ($decoded[0]['tags'] ?? 'Not set') . "\n";
                echo "✅ Pillar Page: " . (($decoded[0]['pillar_page'] ?? false) ? 'Yes' : 'No') . "\n";
            } elseif (isset($decoded['enhanced_content'])) {
                echo "✅ Enhanced content found in response (legacy format)\n";
            } else {
                echo "⚠️  No expected fields found in response\n";
                echo "Expected format: [{\"enhanced_text\": \"...\", \"category\": \"...\", \"subcategory\": \"...\", \"tags\": \"...\", \"pillar_page\": false}]\n";
            }
        } else {
            echo "⚠️  Response is not valid JSON\n";
        }
} else {
    echo "❌ Webhook test failed with status code: " . $response_code . "\n";
    exit(1);
}
?> 