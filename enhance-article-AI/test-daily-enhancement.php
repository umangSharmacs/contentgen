<?php
/**
 * Test script for daily enhancement functionality
 * This script tests the database query and N8N integration
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "🔍 Testing Daily Enhancement Functionality\n";
echo "==========================================\n\n";

// Test 1: Check if N8N webhook is configured
$n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
echo "📋 Test 1: N8N Webhook Configuration\n";
if (!empty($n8n_webhook_url)) {
    echo "✅ N8N webhook URL is configured: " . substr($n8n_webhook_url, 0, 50) . "...\n";
} else {
    echo "❌ N8N webhook URL is not configured\n";
}

// Test 2: Check daily enhancement setting
$daily_enabled = get_option('enhance_article_ai_daily_enabled', '0');
echo "\n📋 Test 2: Daily Enhancement Setting\n";
if ($daily_enabled === '1') {
    echo "✅ Daily enhancement is enabled\n";
} else {
    echo "❌ Daily enhancement is disabled\n";
}

// Test 3: Query for published articles
echo "\n📋 Test 3: Database Query for Published Articles\n";
global $wpdb;

    $today = current_time('Y-m-d');
    $articles = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_content, post_modified 
             FROM {$wpdb->posts} 
             WHERE post_type = %s 
             AND post_status = %s 
             AND DATE(post_date) = %s
             ORDER BY post_modified DESC 
             LIMIT 5",
            'article',
            'publish',
            $today
        )
    );

if (!empty($articles)) {
    echo "✅ Found " . count($articles) . " published articles\n";
    echo "📄 Sample articles:\n";
    foreach ($articles as $article) {
        echo "   - ID: {$article->ID}, Title: " . substr($article->post_title, 0, 50) . "...\n";
        echo "     Modified: {$article->post_modified}\n";
    }
} else {
    echo "❌ No published articles found\n";
    echo "💡 Make sure you have articles with post_type = 'article' and post_status = 'publish'\n";
}

// Test 4: Check if cron job is scheduled
echo "\n📋 Test 4: Cron Job Status\n";
$next_scheduled = wp_next_scheduled('enhance_article_ai_daily_cron');
if ($next_scheduled) {
    echo "✅ Daily cron job is scheduled for: " . date('Y-m-d H:i:s', $next_scheduled) . "\n";
} else {
    echo "❌ Daily cron job is not scheduled\n";
}

// Test 5: Check last run time
echo "\n📋 Test 5: Last Enhancement Run\n";
$last_run = get_option('enhance_article_ai_last_run', 'Never');
if ($last_run !== 'Never') {
    echo "📅 Last run: " . $last_run . " (EDT)\n";
} else {
    echo "📅 Last run: " . $last_run . "\n";
}

// Test 6: Test N8N connectivity (if webhook is configured)
if (!empty($n8n_webhook_url)) {
    echo "\n📋 Test 6: N8N Connectivity Test\n";
    
    $test_data = array(
        'title' => 'Test Article for Daily Enhancement',
        'content' => 'This is a test article to verify N8N connectivity for daily enhancement.',
        'timestamp' => current_time('timestamp')
    );
    
    $response = wp_remote_post($n8n_webhook_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($test_data),
        'timeout' => 180,
    ));
    
    if (is_wp_error($response)) {
        echo "❌ N8N connectivity failed: " . $response->get_error_message() . "\n";
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            echo "✅ N8N connectivity successful (Status: {$response_code})\n";
            
            $response_body = wp_remote_retrieve_body($response);
            $decoded = json_decode($response_body, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "✅ Valid JSON response received\n";
                if (isset($decoded['enhanced_text'])) {
                    echo "✅ Enhanced text field found in response\n";
                } else {
                    echo "⚠️  Enhanced text field not found in response\n";
                }
            } else {
                echo "⚠️  Response is not valid JSON\n";
            }
        } else {
            echo "❌ N8N returned error code: {$response_code}\n";
        }
    }
}

echo "\n🎯 Summary:\n";
echo "==========\n";
echo "1. Configure N8N webhook URL in WordPress settings\n";
echo "2. Enable daily enhancement in plugin settings\n";
echo "3. Ensure you have published articles with post_type = 'article'\n";
echo "4. The cron job will run automatically every 24 hours\n";
echo "5. You can also trigger manual enhancement from the settings page\n";
echo "\n💡 For debugging, check WordPress error logs for detailed information\n";
?>
