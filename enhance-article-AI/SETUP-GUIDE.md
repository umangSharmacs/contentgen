# Quick Setup Guide - Enhance Article AI Plugin

## 🚀 Quick Start

### 1. Install the Plugin
```bash
# Option A: Use the install script
./install.sh

# Option B: Manual installation
# Copy the enhance-article-AI folder to wp-content/plugins/
```

### 2. Activate in WordPress
- Go to WordPress Admin → Plugins
- Find "Enhance Article AI" and click "Activate"

### 3. Configure N8N Webhook
- Go to Settings → Enhance Article AI
- Enter your N8N webhook URL
- Save settings

### 4. Test the Setup
```bash
# Edit test-webhook.php and add your webhook URL
php test-webhook.php
```

## 📋 N8N Workflow Setup

### Basic Workflow Structure
1. **Webhook Trigger** - Receives data from WordPress
2. **AI Processing** - Your AI enhancement logic
3. **Response** - Send enhanced content back

### Expected Input Format
```json
{
  "title": "Post Title",
  "content": "Post content...",
  "timestamp": 1234567890
}
```

### Required Output Format
```json
{
  "enhanced_title": "Enhanced Post Title",
  "enhanced_content": "Enhanced post content...",
  "original_content": "Original post content..."
}
```

## 🎯 Usage

1. **Create/Edit a Post**
   - Go to Posts → Add New
   - Add your content

2. **Enhance with AI**
   - Look for "AI Enhancement" box in sidebar
   - Click "Enhance by AI" button
   - Wait for processing

3. **Review & Apply**
   - Enhanced content appears in modal
   - Compare original vs enhanced
   - Apply to post or copy to clipboard

## 🔧 Troubleshooting

### Common Issues

**Button not appearing:**
- Check plugin is activated
- Verify you're on a post edit page
- Check browser console for errors

**Webhook errors:**
- Verify N8N webhook URL is correct
- Ensure N8N instance is running
- Test webhook manually with test-webhook.php

**Content not enhancing:**
- Check N8N workflow is active
- Verify response format matches expected JSON
- Check WordPress error logs

### Debug Steps
1. Test webhook manually: `php test-webhook.php`
2. Check browser console for JavaScript errors
3. Verify N8N workflow execution history
4. Check WordPress debug logs

## 📁 File Structure
```
enhance-article-AI/
├── enhance-article-ai.php    # Main plugin
├── js/enhance-article-ai.js  # Frontend logic
├── css/enhance-article-ai.css # Styling
├── test-webhook.php          # Webhook testing
├── install.sh               # Installation script
├── README.md                # Full documentation
└── SETUP-GUIDE.md           # This file
```

## 🆘 Support

- Check the main README.md for detailed documentation
- Use test-webhook.php to verify N8N connectivity
- Check WordPress error logs for PHP issues
- Verify browser console for JavaScript errors

---

**Need help?** Check the troubleshooting section or refer to the main README.md file. 