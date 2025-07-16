# Testing ContentGen Plugin in Local by Flywheel

This guide will help you test your WordPress plugin locally before deploying to Bluehost.

## 🚀 **Step 1: Set Up Local by Flywheel**

### 1.1 Create New Site
1. **Open Local by Flywheel**
2. **Click "Create a new site"**
3. **Choose "Custom"** (for more control)
4. **Site Name**: `contentgen-test`
5. **Environment**: **Preferred** (PHP 8.1, MySQL 8.0)
6. **Web Server**: **Nginx** (recommended)
7. **PHP Version**: **8.1** or higher
8. **Database**: **MySQL 8.0**

### 1.2 Site Configuration
- **Admin Username**: `admin`
- **Admin Password**: `password` (or your choice)
- **Admin Email**: `admin@contentgen.local`

## 📦 **Step 2: Install the Plugin**

### 2.1 Access WordPress Admin
1. **Start your Local site**
2. **Click "Admin"** in Local dashboard
3. **Login** with your admin credentials

### 2.2 Upload Plugin
1. **Go to Plugins** → **Add New**
2. **Click "Upload Plugin"**
3. **Choose File**: `contentgen-wordpress-plugin.zip`
4. **Click "Install Now"**
5. **Activate the plugin**

## ⚙️ **Step 3: Configure Plugin Settings**

### 3.1 Access Settings
1. **Go to Settings** → **ContentGen**
2. **Note your Incoming Webhook URL**:
   ```
   http://contentgen-test.local/wp-admin/admin-ajax.php?action=contentgen_webhook
   ```
3. **Copy your Incoming Secret** (auto-generated)

### 3.2 Test Outgoing Webhook (Optional)
For testing outgoing webhooks, you can use:
- **Webhook.site**: Free webhook testing service
- **ngrok**: Tunnel your local n8n instance
- **Mock webhook**: Create a simple test endpoint

## 🧪 **Step 4: Test the Plugin**

### 4.1 Create Dashboard Page
1. **Go to Pages** → **Add New**
2. **Title**: "Research Dashboard"
3. **Add shortcode**: `[contentgen_dashboard]`
4. **Publish the page**
5. **Visit the page** to see the dashboard

### 4.2 Test Incoming Data
Use **Postman** or **curl** to test the webhook:

```bash
curl -X POST http://contentgen-test.local/wp-admin/admin-ajax.php?action=contentgen_webhook \
  -H "Content-Type: application/json" \
  -H "webhook-secret: your_secret_here" \
  -d '{
    "pmid": "12345678",
    "date": "2024-01-15",
    "journal": "Nature Medicine",
    "tweet": "Test tweet content for Local testing...",
    "doi": "10.1038/s41591-024-00001-1",
    "cancerType": "Test Cancer",
    "summary": "This is a test summary for Local testing.",
    "abstract": "This is a test abstract for Local testing purposes.",
    "twitterHashtags": "#Test, #Local",
    "twitterAccounts": "@TestJournal, @TestAuthor",
    "score": 8.5
  }'
```

### 4.3 Test Dashboard Functionality
1. **Accept/Decline tweets**
2. **Edit tweet content**
3. **Export selected tweets**
4. **Test cart functionality**

## 🔧 **Step 5: Debug and Troubleshoot**

### 5.1 Enable Debug Mode
Add to `wp-config.php` (Local will help you edit this):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 5.2 Check Logs
- **WordPress Logs**: `wp-content/debug.log`
- **Local Logs**: Local dashboard → "Logs" tab
- **Browser Console**: F12 → Console tab

### 5.3 Common Local Issues

**Issue**: Plugin not activating
**Solution**: Check PHP version compatibility

**Issue**: Database tables not created
**Solution**: Deactivate/reactivate plugin

**Issue**: AJAX not working
**Solution**: Check Local's SSL settings

## 🎯 **Step 6: Test with n8n (Optional)**

### 6.1 Set Up n8n Locally
1. **Install n8n** locally or use n8n.cloud
2. **Create test workflow** with HTTP Request node
3. **Configure webhook** to point to your Local site

### 6.2 Test Bidirectional Flow
1. **n8n sends data** → WordPress receives
2. **WordPress processes** → User reviews
3. **WordPress sends back** → n8n receives

## 📊 **Step 7: Performance Testing**

### 7.1 Load Testing
- **Send multiple requests** to test performance
- **Monitor Local resources** (CPU, Memory)
- **Check database performance**

### 7.2 Browser Testing
- **Test on different browsers**
- **Test responsive design**
- **Test JavaScript functionality**

## 🔄 **Step 8: Prepare for Production**

### 8.1 Export Test Data
```sql
-- Export research data
SELECT * FROM wp_contentgen_research_data;

-- Export accepted tweets
SELECT * FROM wp_contentgen_accepted_tweets;

-- Export outgoing data
SELECT * FROM wp_contentgen_outgoing_data;
```

### 8.2 Update URLs for Production
Before deploying to Bluehost:
1. **Update webhook URLs** to your domain
2. **Update any hardcoded URLs**
3. **Test with production n8n instance**

## 🐛 **Troubleshooting Local Issues**

### Database Issues
```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_contentgen_%';

-- Check table structure
DESCRIBE wp_contentgen_research_data;
```

### Plugin Issues
1. **Check plugin status** in WordPress admin
2. **Review error logs**
3. **Test with default theme**
4. **Disable other plugins** temporarily

### Webhook Issues
1. **Test with Postman**
2. **Check Local's network settings**
3. **Verify webhook secret**
4. **Check WordPress permalinks**

## ✅ **Success Checklist**

- [ ] Local site created and running
- [ ] Plugin installed and activated
- [ ] Dashboard page created and working
- [ ] Incoming webhook tested successfully
- [ ] Dashboard functionality working
- [ ] Database tables created properly
- [ ] Error logs clean
- [ ] Ready for production deployment

## 🚀 **Next Steps**

Once testing is complete:
1. **Export your Local database** (if needed)
2. **Update URLs** for Bluehost
3. **Deploy to Bluehost** using the setup guide
4. **Test production environment**

Your Local testing environment is now ready! 🎉 