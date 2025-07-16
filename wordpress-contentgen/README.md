# ContentGen - Research Tweet Manager WordPress Plugin

A WordPress plugin for managing research tweets and content generation from n8n workflows with bidirectional data flow.

## Features

- **Multi-phase Tweet Review System**: Query selection, tweet review, and content selection phases
- **Bidirectional n8n Integration**: Receive data from n8n workflows and send processed data back
- **Interactive Tweet Management**: Accept, decline, and edit tweets with right-click functionality
- **Separate Tweet Editing**: Edit Few Shot and No Shot learning tweets independently
- **Content Type Selection**: Choose from Twitter, Clinical Newsletter, and Long Form Newsletter
- **Database Management**: Automatic table creation and data persistence
- **Webhook Security**: Secure webhook endpoints with authentication
- **Shortcode Integration**: Easy embedding with `[contentgen_dashboard]` shortcode

## Installation

1. **Upload the Plugin**:
   - Upload the `wordpress-contentgen` folder to your `/wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin

2. **Activate the Plugin**:
   - Go to WordPress Admin → Plugins
   - Find "ContentGen - Research Tweet Manager" and click "Activate"

3. **Configure Settings**:
   - Go to WordPress Admin → Settings → ContentGen
   - Configure your n8n webhook URLs and secrets

## Usage

### Shortcode
Add the dashboard to any page or post using the shortcode:
```
[contentgen_dashboard]
```

### Webhook Endpoints

#### Incoming Webhook (from n8n)
- **URL**: `https://yoursite.com/wp-admin/admin-ajax.php?action=contentgen_webhook`
- **Method**: POST
- **Headers**: Include `webhook-secret` header with your configured secret
- **Data Format**: JSON with research data fields

#### Outgoing Webhook (to n8n)
- **Configure in**: WordPress Admin → Settings → ContentGen
- **Data Sent**: Accepted tweets and processed content

### Database Tables

The plugin creates three database tables:

1. **`wp_contentgen_research_data`**: Stores incoming research data
2. **`wp_contentgen_accepted_tweets`**: Stores accepted tweets
3. **`wp_contentgen_outgoing_data`**: Logs outgoing webhook data

## Configuration

### Webhook Secrets
- **Incoming Secret**: Automatically generated, used to verify n8n requests
- **Outgoing Secret**: Configure in settings, sent with requests to n8n

### n8n Integration
1. Set up n8n webhook nodes to send data to the incoming webhook URL
2. Configure the outgoing webhook URL in WordPress settings
3. Test the connection using the "Test Connection" button

## Data Flow

1. **n8n → WordPress**: Research data sent via webhook
2. **WordPress Processing**: Data stored in database, available for review
3. **User Review**: Interactive dashboard for tweet review and editing
4. **WordPress → n8n**: Accepted tweets sent back to n8n for further processing

## Features in Detail

### Phase 1: Query Selection
- Initial query selection (currently "cancer")
- Foundation for future multi-query support

### Phase 2: Tweet Review
- Review all incoming tweets
- Decline unwanted tweets (highlighted in pale red)
- Right-click to edit tweets
- Separate editing for Few Shot and No Shot learning tweets
- Expandable summaries and abstracts

### Phase 3: Content Selection
- Accept tweets and select content types
- Auto-selection rules (Twitter → Clinical Newsletter)
- Export selected content

### Tweet Editing
- **Right-click editing**: Right-click on tweet sections to edit
- **Separate sections**: Edit Few Shot and No Shot tweets independently
- **Inline editing**: Cancer tags, hashtags, and mentions
- **Visual feedback**: Hover effects and edit indicators

## Troubleshooting

### Common Issues

1. **Webhook Not Working**:
   - Check webhook URL and secret
   - Verify n8n is sending correct data format
   - Check WordPress error logs

2. **React App Not Loading**:
   - Ensure assets are properly uploaded
   - Check browser console for JavaScript errors
   - Verify shortcode is used correctly

3. **Database Issues**:
   - Deactivate and reactivate plugin to recreate tables
   - Check WordPress database permissions

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### Building the React App
```bash
cd contentgen
npm install
npm run build
cp -r dist/* wordpress-contentgen/assets/
```

### File Structure
```
wordpress-contentgen/
├── contentgen.php          # Main plugin file
├── assets/                 # Built React application
├── templates/              # PHP templates
├── README.md              # This file
└── SETUP-GUIDE.md         # Detailed setup instructions
```

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the SETUP-GUIDE.md for detailed configuration
3. Check WordPress error logs
4. Verify n8n workflow configuration

## Version History

- **1.0.0**: Initial release with multi-phase tweet management system 

Here's how to send a request from n8n to your WordPress application:

## 1. **Webhook URL**
Your WordPress plugin creates a webhook endpoint at:
```
https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook
```

## 2. **Authentication**
Your plugin generates a secret key. You can find it in:
- WordPress Admin → Settings → ContentGen
- Or check the `contentgen_webhook_secret` option in your database

## 3. **HTTP Request Setup in n8n**

### **Method**: POST
### **URL**: `https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook`
### **Headers**:
```
Content-Type: application/json
Webhook-Secret: YOUR_SECRET_KEY_HERE
```

## 4. **Data Format**

### **Single Item**:
```json
{
  "pmid": "12345678",
  "date": "2024-01-15",
  "journal": "Nature",
  "tweet": "New research shows promising results...",
  "doi": "10.1038/nature12345",
  "cancerType": "Breast Cancer",
  "summary": "This study demonstrates...",
  "abstract": "Background: Cancer research...",
  "twitterHashtags": "#cancer #research",
  "twitterAccounts": "@researcher1 @institution",
  "score": 0.85
}
```

### **Batch Processing**:
```json
{
  "batch": true,
  "items": [
    {
      "pmid": "12345678",
      "tweet": "First tweet...",
      "cancerType": "Breast Cancer"
    },
    {
      "pmid": "87654321", 
      "tweet": "Second tweet...",
      "cancerType": "Lung Cancer"
    }
  ]
}
```

## 5. **n8n Node Configuration**

1. **Add HTTP Request node**
2. **Set Method**: POST
3. **Set URL**: Your webhook URL
4. **Add Headers**:
   - `Content-Type`: `application/json`
   - `Webhook-Secret`: Your secret key
5. **Set Body**: Your JSON data

## 6. **Response**
Your WordPress plugin will respond with:
```json
{
  "success": true,
  "data": {
    "message": "Data received and processed successfully",
    "data": {...}
  }
}
```

## 7. **Testing**
You can test the webhook using the test script included in your plugin:
```bash
curl -X POST https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook \
  -H "Content-Type: application/json" \
  -H "Webhook-Secret: YOUR_SECRET" \
  -d '{"pmid":"12345678","tweet":"Test tweet","cancerType":"Test"}'
```

The data will be stored in your WordPress database and will appear in your ContentGen dashboard when you use the shortcode `[contentgen_dashboard]`. 