# n8n Configuration Guide for ContentGen WordPress Plugin

This guide shows you exactly how to configure n8n to send data to your WordPress plugin, including batch processing of multiple items.

## 🎯 **Quick Setup**

### **HTTP Request Node Configuration:**

**Method**: `POST`

**URL**: 
```
https://yourdomain.com/wp-admin/admin-ajax.php?action=contentgen_webhook
```

**Headers**:
```
Content-Type: application/json
webhook-secret: your_secret_here
```

## 📦 **Option 1: Send All Items in One Request (Recommended)**

### **Body (JSON)**:
```json
{
  "batch": true,
  "items": [
    {
      "pmid": "12345678",
      "date": "2024-01-15",
      "journal": "Nature Medicine",
      "tweet": "First research tweet content...",
      "doi": "10.1038/s41591-024-00001-1",
      "cancerType": "Hodgkin Lymphoma",
      "summary": "Study summary 1...",
      "abstract": "Abstract text 1...",
      "twitterHashtags": "#CancerResearch, #Oncology",
      "twitterAccounts": "@NatureMedicine, @ASCO",
      "score": 8.5
    },
    {
      "pmid": "87654321",
      "date": "2024-01-14",
      "journal": "Cell",
      "tweet": "Second research tweet content...",
      "doi": "10.1016/j.cell.2024.01.001",
      "cancerType": "Multi-Cancer",
      "summary": "Study summary 2...",
      "abstract": "Abstract text 2...",
      "twitterHashtags": "#CancerInterception, #MCED",
      "twitterAccounts": "@CellPressNews, @CancerResearch",
      "score": 9.2
    }
  ]
}
```

## 🔄 **Option 2: Use n8n's Built-in Batching**

### **HTTP Request Node Settings:**
- **Method**: `POST`
- **URL**: Same as above
- **Headers**: Same as above
- **Batch Size**: Set to 10-20 items per request
- **Batch Interval**: 1000ms (1 second between batches)

### **Body (JSON)**:
```json
{
  "pmid": "{{ $json.pmid }}",
  "date": "{{ $json.date }}",
  "journal": "{{ $json.journal }}",
  "tweet": "{{ $json.tweet }}",
  "doi": "{{ $json.doi }}",
  "cancerType": "{{ $json.cancerType }}",
  "summary": "{{ $json.summary }}",
  "abstract": "{{ $json.abstract }}",
  "twitterHashtags": "{{ $json.twitterHashtags }}",
  "twitterAccounts": "{{ $json.twitterAccounts }}",
  "score": "{{ $json.score }}"
}
```

## 📊 **Complete Workflow Examples**

### **Example 1: Google Sheets to WordPress**

#### **Step 1: Google Sheets Node**
- **Operation**: Read
- **Sheet**: Your research data sheet
- **Range**: A:J (or your data range)
- **Output**: All rows as separate items

#### **Step 2: Set Node (Data Formatting)**
```json
{
  "pmid": "{{ $json['PMID'] }}",
  "date": "{{ $json['Date'] }}",
  "journal": "{{ $json['Journal'] }}",
  "tweet": "{{ $json['Tweet'] }}",
  "doi": "{{ $json['DOI'] }}",
  "cancerType": "{{ $json['Cancer Type'] }}",
  "summary": "{{ $json['Summary'] }}",
  "abstract": "{{ $json['Abstract'] }}",
  "twitterHashtags": "{{ $json['Twitter Hashtags'] }}",
  "twitterAccounts": "{{ $json['Twitter Accounts'] }}",
  "score": "{{ $json['Score'] }}"
}
```

#### **Step 3: HTTP Request Node**
- **Method**: POST
- **URL**: Your WordPress webhook URL
- **Headers**: Content-Type and webhook-secret
- **Body**: Use the mapped data from Set node

### **Example 2: CSV File to WordPress**

#### **Step 1: CSV Node**
- **File**: Upload your CSV file
- **Delimiter**: Comma
- **Output**: All rows as separate items

#### **Step 2: Set Node (Data Formatting)**
Same as Google Sheets example

#### **Step 3: HTTP Request Node**
Same configuration

### **Example 3: Database to WordPress**

#### **Step 1: Database Node**
- **Operation**: Select
- **Query**: `SELECT * FROM research_data WHERE processed = 0`
- **Output**: All rows as separate items

#### **Step 2: Set Node (Data Formatting)**
Same as above

#### **Step 3: HTTP Request Node**
Same configuration

## 🚀 **Batch Processing for 50+ Items**

### **Method 1: Single Request with All Items**
```json
{
  "batch": true,
  "items": [
    // All 50 items here
  ]
}
```

### **Method 2: Multiple Batches**
Configure HTTP Request node with:
- **Batch Size**: 10
- **Batch Interval**: 1000ms
- **Continue on Fail**: true

This will send 10 items at a time with 1-second delays.

## 🔧 **Advanced Configuration**

### **Error Handling**
Add an **IF** node after HTTP Request:
- **Condition**: `{{ $json.success === false }}`
- **True**: Send notification (email/Slack)
- **False**: Continue processing

### **Success Notification**
Add a **Send Email** or **Slack** node:
- **Trigger**: When batch processing completes
- **Message**: Include success/failure counts

### **Data Validation**
Add a **Set** node before HTTP Request:
```json
{
  "validated": true,
  "pmid": "{{ $json.pmid || '' }}",
  "date": "{{ $json.date || new Date().toISOString().split('T')[0] }}",
  "journal": "{{ $json.journal || 'Unknown Journal' }}",
  "tweet": "{{ $json.tweet || 'No tweet content' }}",
  "doi": "{{ $json.doi || '' }}",
  "cancerType": "{{ $json.cancerType || 'General' }}",
  "summary": "{{ $json.summary || 'No summary available' }}",
  "abstract": "{{ $json.abstract || '' }}",
  "twitterHashtags": "{{ $json.twitterHashtags || '' }}",
  "twitterAccounts": "{{ $json.twitterAccounts || '' }}",
  "score": "{{ $json.score || 0 }}"
}
```

## 📋 **Testing Your Configuration**

### **Test with Single Item**
```json
{
  "pmid": "12345678",
  "date": "2024-01-15",
  "journal": "Nature Medicine",
  "tweet": "Test tweet content...",
  "doi": "10.1038/s41591-024-00001-1",
  "cancerType": "Test Cancer",
  "summary": "Test summary...",
  "abstract": "Test abstract...",
  "twitterHashtags": "#Test",
  "twitterAccounts": "@TestJournal",
  "score": 8.5
}
```

### **Test with Batch**
```json
{
  "batch": true,
  "items": [
    {
      "pmid": "12345678",
      "tweet": "Test tweet 1...",
      "score": 8.5
    },
    {
      "pmid": "87654321",
      "tweet": "Test tweet 2...",
      "score": 9.2
    }
  ]
}
```

## ✅ **Expected Responses**

### **Single Item Success**
```json
{
  "success": true,
  "data": {
    "message": "Data received and processed successfully",
    "data": {
      "pmid": "12345678",
      "status": "pending"
    }
  }
}
```

### **Batch Success**
```json
{
  "success": true,
  "data": {
    "message": "Batch data processed successfully",
    "processed_count": 48,
    "failed_count": 2,
    "success_items": [...],
    "failed_items": [...]
  }
}
```

## 🐛 **Troubleshooting**

### **Common Issues**

1. **401 Unauthorized**
   - Check webhook secret in headers
   - Verify secret in WordPress admin

2. **400 Bad Request**
   - Check JSON format
   - Verify required fields

3. **Timeout Errors**
   - Reduce batch size
   - Increase timeout in n8n settings

4. **Memory Issues**
   - Process smaller batches
   - Add delays between requests

### **Debug Steps**
1. **Test with single item first**
2. **Check WordPress error logs**
3. **Verify webhook URL**
4. **Test with Postman/curl**

## 🎉 **Success Indicators**

✅ **Single Item**: WordPress dashboard shows new research card
✅ **Batch Processing**: Multiple cards appear in dashboard
✅ **Error Handling**: Failed items are logged but don't stop processing
✅ **Performance**: All 50 items processed within reasonable time

Your n8n workflow is now ready to send data to WordPress! 🚀 