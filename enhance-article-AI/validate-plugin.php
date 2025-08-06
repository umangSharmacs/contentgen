<?php
/**
 * Plugin Validation Script
 * This script validates that the plugin is properly formatted for WordPress
 */

echo "🔍 Validating Enhance Article AI Plugin...\n";
echo "==========================================\n\n";

// Check if main plugin file exists
$plugin_file = __DIR__ . '/enhance-article-ai.php';
if (!file_exists($plugin_file)) {
    echo "❌ Main plugin file not found: enhance-article-ai.php\n";
    exit(1);
}

echo "✅ Main plugin file found\n";

// Check plugin header
$plugin_content = file_get_contents($plugin_file);
if (strpos($plugin_content, 'Plugin Name:') === false) {
    echo "❌ Plugin header not found in main file\n";
    exit(1);
}

echo "✅ Plugin header found\n";

// Check required files
$required_files = [
    'js/enhance-article-ai.js',
    'css/enhance-article-ai.css'
];

foreach ($required_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (!file_exists($file_path)) {
        echo "❌ Required file not found: $file\n";
        exit(1);
    }
    echo "✅ Required file found: $file\n";
}

// Check file sizes
$file_sizes = [
    'enhance-article-ai.php' => 1000, // Should be at least 1KB
    'js/enhance-article-ai.js' => 100, // Should be at least 100B
    'css/enhance-article-ai.css' => 100 // Should be at least 100B
];

foreach ($file_sizes as $file => $min_size) {
    $file_path = __DIR__ . '/' . $file;
    $size = filesize($file_path);
    if ($size < $min_size) {
        echo "❌ File too small: $file ($size bytes, expected at least $min_size)\n";
        exit(1);
    }
    echo "✅ File size OK: $file ($size bytes)\n";
}

// Check for PHP syntax errors
$syntax_check = shell_exec("php -l $plugin_file 2>&1");
if (strpos($syntax_check, 'No syntax errors') === false) {
    echo "❌ PHP syntax error in main plugin file:\n$syntax_check\n";
    exit(1);
}

echo "✅ PHP syntax is valid\n";

// Check for required WordPress functions
$required_functions = [
    'add_action',
    'add_meta_box',
    'wp_enqueue_script',
    'wp_enqueue_style',
    'wp_remote_post',
    'wp_send_json_success',
    'wp_send_json_error'
];

foreach ($required_functions as $function) {
    if (strpos($plugin_content, $function) === false) {
        echo "❌ Required WordPress function not found: $function\n";
        exit(1);
    }
}

echo "✅ All required WordPress functions found\n";

// Check for security features
$security_features = [
    'ABSPATH',
    'wp_verify_nonce',
    'sanitize_text_field',
    'sanitize_textarea_field',
    'esc_attr'
];

foreach ($security_features as $feature) {
    if (strpos($plugin_content, $feature) === false) {
        echo "⚠️  Security feature not found: $feature\n";
    } else {
        echo "✅ Security feature found: $feature\n";
    }
}

echo "\n🎉 Plugin validation completed successfully!\n";
echo "The plugin is ready for WordPress installation.\n\n";

echo "📋 Installation Instructions:\n";
echo "1. Copy the enhance-article-AI folder to wp-content/plugins/\n";
echo "2. Go to WordPress Admin → Plugins\n";
echo "3. Find 'Enhance Article AI' and click 'Activate'\n";
echo "4. Go to Settings → Enhance Article AI\n";
echo "5. Enter your N8N webhook URL\n";
echo "6. Save settings\n\n";

echo "🔧 Testing:\n";
echo "- Use test-webhook.php to verify N8N connectivity\n";
echo "- Check browser console for any JavaScript errors\n";
echo "- Verify the 'AI Enhancement' box appears in post editor\n"; 