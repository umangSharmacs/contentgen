#!/bin/bash

# WordPress ContentGen Plugin Deployment Script
echo "🚀 Creating WordPress ContentGen Plugin Package..."

# Create plugin directory
PLUGIN_DIR="contentgen-wordpress-plugin"
mkdir -p $PLUGIN_DIR

# Copy plugin files
echo "📁 Copying plugin files..."
cp -r wordpress-contentgen/* $PLUGIN_DIR/

# Create zip file
echo "📦 Creating plugin zip file..."
zip -r contentgen-wordpress-plugin.zip $PLUGIN_DIR/

# Clean up
rm -rf $PLUGIN_DIR

echo "✅ WordPress plugin package created: contentgen-wordpress-plugin.zip"
echo ""
echo "📋 Next steps for Bluehost WordPress deployment:"
echo "1. Log into your Bluehost WordPress admin panel"
echo "2. Go to Plugins → Add New"
echo "3. Click 'Upload Plugin'"
echo "4. Choose 'contentgen-wordpress-plugin.zip'"
echo "5. Install and activate the plugin"
echo "6. Add the shortcode [contentgen_dashboard] to any page"
echo "7. Configure your n8n webhook to point to:"
echo "   https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook" 