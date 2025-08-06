# Enhance Article AI - WordPress Plugin

A minimal WordPress plugin that adds an "Enhance by AI" button to the post editor. The button sends post content to an N8N webhook for AI enhancement and displays the enhanced content in a modal window.

## Features

- ✅ Adds "Enhance by AI" button to WordPress post editor
- ✅ Works with both Gutenberg and Classic editors
- ✅ Sends content to N8N webhook for processing
- ✅ Displays enhanced content in a beautiful modal window
- ✅ Side-by-side comparison of original vs enhanced content
- ✅ Copy enhanced content to clipboard
- ✅ Apply enhanced content directly to the post
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

1. **Create or edit a post:**
   - Go to Posts → Add New (or edit an existing post)
   - Add some content to your post

2. **Enhance with AI:**
   - Look for the "AI Enhancement" meta box in the sidebar
   - Click the "Enhance by AI" button
   - Wait for the AI processing to complete

3. **Review enhanced content:**
   - The enhanced content will appear in a modal window
   - Switch between "Enhanced Version" and "Original Version" tabs
   - Edit the enhanced content if needed

4. **Apply or copy:**
   - Click "Apply to Post" to replace your current content
   - Or click "Copy Enhanced" to copy to clipboard
   - Click "Close" to dismiss the modal

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
├── enhance-article-ai.php    # Main plugin file
├── js/
│   └── enhance-article-ai.js # JavaScript functionality
├── css/
│   └── enhance-article-ai.css # Styles for modal and button
└── README.md                 # This file
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
- Ensure the response includes `enhanced_content` field

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