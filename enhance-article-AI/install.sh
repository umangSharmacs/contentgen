#!/bin/bash

# Enhance Article AI Plugin Installation Script
# This script helps install the plugin to a WordPress site

echo "🚀 Enhance Article AI Plugin Installation"
echo "=========================================="

# Check if WordPress is detected
if [ ! -f "wp-config.php" ] && [ ! -f "../wp-config.php" ]; then
    echo "❌ WordPress not detected in current directory or parent directory"
    echo "Please run this script from your WordPress root directory or wp-content/plugins/"
    exit 1
fi

# Determine WordPress path
if [ -f "wp-config.php" ]; then
    WP_ROOT="."
    PLUGIN_DIR="wp-content/plugins"
elif [ -f "../wp-config.php" ]; then
    WP_ROOT=".."
    PLUGIN_DIR="plugins"
fi

# Create plugin directory if it doesn't exist
if [ ! -d "$WP_ROOT/$PLUGIN_DIR" ]; then
    echo "📁 Creating plugins directory..."
    mkdir -p "$WP_ROOT/$PLUGIN_DIR"
fi

# Copy plugin files
echo "📋 Copying plugin files..."
cp -r enhance-article-ai "$WP_ROOT/$PLUGIN_DIR/"

# Set proper permissions
echo "🔐 Setting permissions..."
chmod 755 "$WP_ROOT/$PLUGIN_DIR/enhance-article-ai"
chmod 644 "$WP_ROOT/$PLUGIN_DIR/enhance-article-ai/enhance-article-ai.php"
chmod 644 "$WP_ROOT/$PLUGIN_DIR/enhance-article-ai/js/enhance-article-ai.js"
chmod 644 "$WP_ROOT/$PLUGIN_DIR/enhance-article-ai/css/enhance-article-ai.css"
chmod 644 "$WP_ROOT/$PLUGIN_DIR/enhance-article-ai/README.md"

echo "✅ Installation complete!"
echo ""
echo "📝 Next steps:"
echo "1. Go to your WordPress admin panel"
echo "2. Navigate to Plugins → Installed Plugins"
echo "3. Find 'Enhance Article AI' and click 'Activate'"
echo "4. Go to Settings → Enhance Article AI"
echo "5. Enter your N8N webhook URL"
echo "6. Save settings"
echo ""
echo "🎉 Plugin is ready to use!"
echo ""
echo "💡 Tip: Use the test-webhook.php file to verify your N8N webhook is working correctly" 