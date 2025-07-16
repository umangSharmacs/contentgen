# ContentGen WordPress + n8n Setup Guide

This guide will help you set up a complete bidirectional data flow between n8n and your WordPress site on Bluehost.

## 🎯 **Complete Data Flow**

```
n8n → WordPress (Receive Data) → Review/Edit → WordPress → n8n (Send Data)
```

## 📋 **Prerequisites**

1. **Bluehost WordPress Site**: Active WordPress installation
2. **n8n Instance**: Running n8n workflow automation
3. **ContentGen Plugin**: WordPress plugin installed

## 🚀 **Step 1: Install WordPress Plugin**

### 1.1 Upload Plugin
1. Log into your Bluehost WordPress admin
2. Go to **Plugins** → **Add New**
3. Click **Upload Plugin**
4. Choose `contentgen-wordpress-plugin.zip`
5. Install and activate

### 1.2 Configure Plugin Settings
1. Go to **Settings** → **ContentGen**
2. Note your **Incoming Webhook URL** and **Secret**
3. Configure your **Outgoing Webhook URL** (n8n webhook)
4. Test the outgoing connection

## 🔗 **Step 2: Configure n8n Workflows**

### 2.1 Incoming Data Workflow (n8n → WordPress)

Create a workflow that sends research data to WordPress:

**HTTP Request Node Configuration:**
- **Method**: POST
- **URL**: `https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook`
- **Headers**:
  ```
  Content-Type: application/json
  webhook-secret: your_incoming_secret_here
  ```
- **Body** (JSON):
  ```json
  {
    "pmid": "12345678",
    "date": "2024-01-15",
    "journal": "Nature Medicine",
    "tweet": "Your tweet content here...",
    "doi": "10.1038/s41591-024-00001-1",
    "cancerType": "Hodgkin Lymphoma",
    "summary": "Study summary...",
    "abstract": "Full abstract text...",
    "twitterHashtags": "#CancerResearch, #Oncology",
    "twitterAccounts": "@NatureMedicine, @ASCO",
    "score": 8.5
  }
  ```

### 2.2 Outgoing Data Workflow (WordPress → n8n)

Create a webhook trigger in n8n to receive data from WordPress:

**Webhook Node Configuration:**
- **HTTP Method**: POST
- **Path**: `/wordpress-contentgen`
- **Authentication**: Header
- **Header Name**: `webhook-secret`
- **Header Value**: `your_outgoing_secret_here`

**Expected Data Format:**
```json
{
  "type": "accepted_tweets",
  "data": [
    {
      "pmid": "12345678",
      "tweet": "Accepted tweet content...",
      "journal": "Nature Medicine",
      "date": "2024-01-15",
      "cancer_type": "Hodgkin Lymphoma"
    }
  ],
  "timestamp": "2024-01-15T10:30:00Z",
  "source": "wordpress_contentgen"
}
```

## 🎨 **Step 3: Add Dashboard to Your Site**

### 3.1 Create Dashboard Page
1. Go to **Pages** → **Add New**
2. Add the shortcode: `[contentgen_dashboard]`
3. Publish the page

### 3.2 Customize Dashboard (Optional)
```php
[contentgen_dashboard title="My Research Manager"]
```

## 🔄 **Step 4: Test the Complete Flow**

### 4.1 Test Incoming Data
1. **Trigger n8n workflow** with sample data
2. **Check WordPress dashboard** for new research cards
3. **Verify data** appears correctly

### 4.2 Test Outgoing Data
1. **Accept some tweets** in WordPress dashboard
2. **Export selected tweets**
3. **Check n8n webhook** receives the data
4. **Verify data format** in n8n

## 📊 **Data Flow Examples**

### Example 1: Research Paper Processing
```
1. n8n scrapes PubMed → Sends to WordPress
2. WordPress displays research card
3. User reviews and edits tweet
4. User accepts tweet
5. WordPress sends accepted tweet to n8n
6. n8n posts to Twitter/Social Media
```

### Example 2: Batch Processing
```
1. n8n sends multiple research papers
2. WordPress displays all cards
3. User selects multiple tweets
4. User exports batch
5. WordPress sends batch to n8n
6. n8n processes and schedules posts
```

## 🔧 **Advanced Configuration**

### Custom Data Types
You can send custom data types to n8n:

```javascript
// Send custom data
sendToN8n('custom_type', {
    action: 'user_feedback',
    feedback: 'Great research!',
    timestamp: new Date().toISOString()
});
```

### Error Handling
The plugin includes:
- **Retry logic** for failed webhook calls
- **Logging** of all webhook attempts
- **Error notifications** in WordPress admin

### Security Features
- **Webhook secrets** for authentication
- **Nonce verification** for AJAX requests
- **Input sanitization** for all data
- **SQL prepared statements** for database queries

## 🐛 **Troubleshooting**

### Common Issues

1. **Webhook Not Receiving Data**
   - Check webhook URL is correct
   - Verify webhook secret matches
   - Check WordPress error logs
   - Test with Postman/curl

2. **Data Not Sending to n8n**
   - Verify outgoing webhook URL
   - Check n8n webhook is active
   - Test connection in WordPress admin
   - Check network connectivity

3. **Dashboard Not Loading**
   - Ensure plugin is activated
   - Check shortcode is correct
   - Verify JavaScript console for errors
   - Check WordPress permissions

### Debug Mode
Enable WordPress debug mode:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testing Tools
1. **Postman**: Test webhook endpoints
2. **Browser Console**: Check JavaScript errors
3. **WordPress Logs**: Check error logs
4. **n8n Logs**: Check webhook triggers

## 📈 **Monitoring and Analytics**

### WordPress Admin
- **Settings page** shows webhook status
- **Database tables** track all data
- **Error logs** for debugging

### n8n Monitoring
- **Webhook trigger logs**
- **Workflow execution history**
- **Error notifications**

## 🔄 **Data Flow Diagram**

```
┌─────────────┐    POST     ┌──────────────┐    Review    ┌─────────────┐
│    n8n      │ ──────────→ │  WordPress   │ ──────────→ │    User     │
│ Workflow    │   Research  │   Plugin     │   Interface │  Dashboard  │
└─────────────┘    Data     └──────────────┘             └─────────────┘
       ↑                                                    │
       │                                                    │ Accept/Edit
       │                                                    ▼
       │                                            ┌──────────────┐
       │                                            │  WordPress   │
       │                                            │   Plugin     │
       │                                            └──────────────┘
       │                                                    │
       │                                                    │ POST
       │                                                    ▼
       │                                            ┌─────────────┐
       │                                            │    n8n      │
       │                                            │  Webhook    │
       │                                            │  Trigger    │
       └────────────────────────────────────────────┘             │
                                                                  │
                                                                  ▼
                                                         ┌─────────────┐
                                                         │    n8n      │
                                                         │ Workflow    │
                                                         │ (Twitter,   │
                                                         │  etc.)      │
                                                         └─────────────┘
```

## 🎉 **Success Indicators**

✅ **Incoming Flow Working:**
- Research cards appear in WordPress dashboard
- Data is stored in WordPress database
- No errors in WordPress logs

✅ **Outgoing Flow Working:**
- Accepted tweets trigger n8n webhook
- n8n receives data in correct format
- No failed webhook attempts in WordPress

✅ **Complete Integration:**
- Bidirectional data flow operational
- Error handling working
- Security measures active

## 📞 **Support**

If you encounter issues:
1. Check this setup guide
2. Review WordPress error logs
3. Test webhook endpoints individually
4. Verify n8n workflow configurations
5. Check network connectivity

Your bidirectional n8n + WordPress integration is now ready! 🚀 