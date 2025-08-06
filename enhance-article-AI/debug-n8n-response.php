<?php
/**
 * Debug N8N Response Script
 * This script helps debug what's being received from N8N
 */

// Simulate your N8N response format (single object, not array)
$test_response = '{
"enhanced_text": "This appears to be a placeholder text without cancer-related content. To help you best, please provide a cancer education article or topic you\'d like me to enhance for your Together4Cancer platform. If you\'d like, I can also create an example article on a common cancer topic such as prevention, symptoms, or treatment basics. Just let me know!",
"subcategory": "Miscellaneous",
"category": "Self-Advocacy and Cancer",
"tags": "[\"cancer\",\"education\",\"placeholder\"]",
"pillar_page": ""
}';

echo "🔍 Testing N8N Response Processing\n";
echo "================================\n\n";

echo "📥 Raw Response:\n";
echo $test_response . "\n\n";

// Test JSON decoding
$decoded = json_decode($test_response, true);
$json_error = json_last_error();

echo "🔧 JSON Decode Test:\n";
if ($json_error !== JSON_ERROR_NONE) {
    echo "❌ JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "✅ JSON Decoded Successfully\n";
    echo "📊 Decoded Structure: " . gettype($decoded) . "\n";
    echo "📊 Array Count: " . (is_array($decoded) ? count($decoded) : 'Not an array') . "\n\n";
    
    if (is_array($decoded) && !empty($decoded)) {
        // Check if it's an array of objects or a single object
        if (isset($decoded[0]) && is_array($decoded[0])) {
            // Array format: [{...}]
            $first_item = $decoded[0];
            echo "📋 Array Format Detected - First Item Fields:\n";
        } elseif (isset($decoded['enhanced_text'])) {
            // Single object format: {...}
            $first_item = $decoded;
            echo "📋 Single Object Format Detected - Fields:\n";
        } else {
            echo "❌ Unexpected format\n";
            return;
        }
        
        echo "- enhanced_text: " . (isset($first_item['enhanced_text']) ? '✅ Found' : '❌ Missing') . "\n";
        echo "- subcategory: " . (isset($first_item['subcategory']) ? '✅ Found' : '❌ Missing') . "\n";
        echo "- category: " . (isset($first_item['category']) ? '✅ Found' : '❌ Missing') . "\n";
        echo "- tags: " . (isset($first_item['tags']) ? '✅ Found' : '❌ Missing') . "\n";
        echo "- pillar_page: " . (isset($first_item['pillar_page']) ? '✅ Found' : '❌ Missing') . "\n\n";
        
        echo "📄 Extracted Values:\n";
        echo "- Enhanced Text: " . substr($first_item['enhanced_text'] ?? 'N/A', 0, 100) . "...\n";
        echo "- Category: " . ($first_item['category'] ?? 'N/A') . "\n";
        echo "- Subcategory: " . ($first_item['subcategory'] ?? 'N/A') . "\n";
        echo "- Tags: " . ($first_item['tags'] ?? 'N/A') . "\n";
        echo "- Pillar Page: " . (($first_item['pillar_page'] ?? false) ? 'Yes' : 'No') . "\n";
    }
}

echo "\n🎯 Plugin Processing Test:\n";

// Simulate the plugin's processing logic
if (is_array($decoded) && !empty($decoded)) {
    // Check if response is an array or single object
    if (isset($decoded[0]) && is_array($decoded[0])) {
        // If it's an array, take the first item
        $first_item = $decoded[0];
    } elseif (isset($decoded['enhanced_text'])) {
        // If it's a single object (not in array)
        $first_item = $decoded;
    } else {
        // Fallback - treat as single object
        $first_item = $decoded;
    }
    
    $enhanced_content = array(
        'enhanced_text' => isset($first_item['enhanced_text']) ? $first_item['enhanced_text'] : '',
        'subcategory' => isset($first_item['subcategory']) ? $first_item['subcategory'] : '',
        'category' => isset($first_item['category']) ? $first_item['category'] : '',
        'tags' => isset($first_item['tags']) ? $first_item['tags'] : '',
        'pillar_page' => isset($first_item['pillar_page']) ? $first_item['pillar_page'] : false,
        'original_content' => 'Test original content',
        'original_title' => 'Test original title',
        'debug_info' => 'Successfully processed N8N response'
    );
    
    echo "✅ Plugin processing successful\n";
    echo "📤 Final output structure:\n";
    foreach ($enhanced_content as $key => $value) {
        if ($key === 'enhanced_text') {
            echo "- $key: " . substr($value, 0, 50) . "...\n";
        } else {
            echo "- $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
    }
} else {
    echo "❌ Plugin processing failed - invalid response structure\n";
}

echo "\n💡 Troubleshooting Tips:\n";
echo "1. Check your N8N webhook URL in WordPress settings\n";
echo "2. Verify N8N workflow is active and returning 200 status\n";
echo "3. Check WordPress error logs for detailed debug info\n";
echo "4. Use test-webhook.php to test direct N8N connectivity\n";
echo "5. Ensure N8N response matches the expected JSON format\n";
?> 