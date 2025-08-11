# Enhance Article AI - WordPress Plugin

A minimal WordPress plugin that adds an "Enhance by AI" button to the post editor. The button sends post content to an N8N webhook for AI enhancement and displays the enhanced content in a modal window.

## Features

- ✅ Adds "Enhance by AI" button to WordPress post editor
- ✅ **NEW: Pure PHP implementation - no JavaScript required**
- ✅ Works with both Gutenberg and Classic editors
- ✅ Sends content to N8N webhook for processing
- ✅ **NEW: Form-based enhancement with preview and apply functionality**
- ✅ **NEW: Admin notices for user feedback**
- ✅ **NEW: Daily automatic enhancement of published articles**
- ✅ **NEW: Manual enhancement trigger from settings**
- ✅ **NEW: Built-in system testing functionality**
- ✅ **NEW: Comprehensive logging and monitoring**
- ✅ No database required - minimal footprint
- ✅ Responsive design for mobile devices

## Installation

1. **Upload the plugin:**
   - Copy the `enhance-article-AI` folder to your WordPress `wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin

2. **Activate the plugin:**
   - Go to WordPress Admin → Plugins
   - Find "Enhance Article AI" and click "Activate"

3. **Configure the N8N webhook:**
   - Go to WordPress Admin → Settings → Enhance Article AI
   - Enter your N8N webhook URL
   - Click "Save Changes"

## Usage

### Manual Enhancement
1. **Create or edit a post:**
   - Go to Posts → Add New (or edit an existing post)
   - Add some content to your post

2. **Enhance with AI:**
   - Look for the "AI Enhancement" meta box in the sidebar
   - Click the "Enhance by AI" button
   - The system will process your content and redirect you back

3. **Review enhanced content:**
   - Enhanced content preview will appear in the sidebar
   - Review the enhanced text before applying

4. **Apply enhanced content:**
   - Click "Apply Enhanced Content" to replace your current content
   - Or click "Clear Result" to discard the enhancement

### Automatic Daily Enhancement
1. **Configure the feature:**
   - Go to Settings → Enhance Article AI
   - Enable "Daily Auto-Enhancement"
   - Ensure your N8N webhook URL is configured

2. **Automatic processing:**
   - The plugin will automatically enhance all published articles from today every 24 hours
   - Only articles with `post_type = 'article'`, `post_status = 'publish'`, and `post_date = today` are processed
   - Enhanced content automatically replaces the original content

3. **Manual trigger:**
   - Use the "Run Enhancement Now" button in settings to trigger immediate processing
   - Monitor the "Last Enhancement Run" timestamp for tracking

4. **Monitoring:**
   - Check WordPress error logs for detailed processing information
   - Use the test script `test-daily-enhancement.php` to verify functionality

## N8N Webhook Configuration

Your N8N webhook should expect a JSON payload with the following structure:

```json
{
  "title": "Post Title",
  "content": "Post content...",
  "timestamp": 1234567890
}
```

The webhook should return a JSON response with enhanced content:

```json
{
  "enhanced_text": "Enhanced article content...",
  "subcategory": "Miscellaneous",
  "category": "Self-Advocacy and Cancer",
  "tags": "[\"cancer\",\"education\",\"placeholder\"]",
  "pillar_page": ""
}
```

**Note**: The plugin supports both single object format (above) and array format `[{...}]`.

## File Structure

```
enhance-article-AI/
├── enhance-article-ai.php           # Main plugin file (pure PHP)
├── css/
│   └── enhance-article-ai.css       # Styles for forms and interface
├── test-webhook.php                 # N8N webhook testing script
├── test-daily-enhancement.php       # Daily enhancement testing script
├── debug-n8n-response.php           # N8N response debugging script
├── install.sh                       # Installation script
├── README.md                        # This file
└── SETUP-GUIDE.md                   # Setup guide
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- N8N instance with webhook endpoint

## Troubleshooting

### Button not appearing
- Make sure the plugin is activated
- Check that you're on a post edit page (not pages or other post types)
- Verify JavaScript is enabled in your browser

### Webhook errors
- Check your N8N webhook URL in Settings → Enhance Article AI
- Ensure your N8N instance is running and accessible
- Check browser console for any JavaScript errors

### Content not enhancing
- Verify your N8N workflow is properly configured
- Check that the webhook returns valid JSON
- Ensure the response includes `enhanced_text` field

### Daily enhancement not working
- Check if daily enhancement is enabled in settings
- Verify you have articles with `post_type = 'article'` and `post_status = 'publish'`
- Run `test-daily-enhancement.php` to diagnose issues
- Check WordPress error logs for detailed information
- Ensure your N8N webhook is accessible and returning valid responses

## Customization

### Styling
You can customize the appearance by modifying `css/enhance-article-ai.css`.

### JavaScript
Modify `js/enhance-article-ai.js` to change the behavior of the enhancement process.

### PHP
Edit `enhance-article-ai.php` to modify the server-side logic or add new features.

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your N8N webhook is working correctly
3. Check WordPress error logs for any PHP errors

## License

This plugin is provided as-is for educational and development purposes. 