# Bluehost Deployment Guide

This guide will help you deploy your ContentGen application to Bluehost.

## Prerequisites

1. **Bluehost Account**: You need a Bluehost hosting plan with Node.js support
2. **Domain**: Your domain should be configured to point to Bluehost
3. **File Manager Access**: Access to Bluehost's file manager or FTP

## Step 1: Prepare Your Application

### Option A: Use the Deployment Script (Recommended)
```bash
# Make the script executable
chmod +x deploy.sh

# Run the deployment script
./deploy.sh
```

### Option B: Manual Preparation
```bash
# Build the React application
npm run build

# Create deployment directory
mkdir deploy
cp -r dist/* deploy/
cp server/server.js deploy/
cp server/package.json deploy/
cp server/env.example deploy/.env
```

## Step 2: Configure Bluehost

### 2.1 Enable Node.js
1. Log into your Bluehost control panel
2. Navigate to **Advanced** → **Node.js**
3. Click **Create Application**
4. Configure the following:
   - **Node.js version**: 18.x or higher
   - **Application mode**: Production
   - **Application root**: `/public_html` (or your domain directory)
   - **Application URL**: Your domain (e.g., `https://yourdomain.com`)
   - **Application startup file**: `server.js`
   - **Node.js application port**: `3001`

### 2.2 Set Environment Variables
In the Node.js configuration, add these environment variables:
- `NODE_ENV=production`
- `PORT=3001`
- `WEBHOOK_SECRET=your_secure_secret_here`

## Step 3: Upload Files

### Option A: Using File Manager
1. Go to **Files** → **File Manager**
2. Navigate to `public_html` directory
3. Upload all files from your `deploy` folder
4. Make sure `server.js` is in the root of `public_html`

### Option B: Using FTP
1. Use an FTP client (FileZilla, Cyberduck, etc.)
2. Connect to your Bluehost server
3. Upload all files from `deploy` folder to `public_html`

## Step 4: Install Dependencies

### Via SSH (if available)
```bash
cd public_html
npm install --production
```

### Via Bluehost Terminal
1. Go to **Advanced** → **Terminal**
2. Navigate to your domain directory
3. Run: `npm install --production`

## Step 5: Start the Application

1. In Bluehost Node.js panel, click **Restart** on your application
2. The application should start automatically
3. Check the logs for any errors

## Step 6: Test Your Application

1. Visit your domain: `https://yourdomain.com`
2. Test the health endpoint: `https://yourdomain.com/health`
3. Test the webhook: `https://yourdomain.com/webhook/n8n`

## Step 7: Configure n8n Webhook

Update your n8n workflow to use your new domain:
- **URL**: `https://yourdomain.com/webhook/n8n`
- **Method**: POST
- **Headers**: Add `webhook-secret: your_secure_secret_here` if configured

## Troubleshooting

### Common Issues

1. **Application won't start**
   - Check Node.js version compatibility
   - Verify `server.js` is in the correct location
   - Check application logs in Bluehost panel

2. **404 errors**
   - Ensure `.htaccess` file is uploaded
   - Verify file permissions (644 for files, 755 for directories)

3. **Port issues**
   - Bluehost typically uses port 3001 for Node.js apps
   - Check your application configuration

4. **Environment variables**
   - Verify all environment variables are set in Bluehost Node.js panel
   - Check that `.env` file is properly configured

### Logs and Debugging

1. **Bluehost Logs**: Check the Node.js application logs in your Bluehost control panel
2. **Application Logs**: Your server logs will appear in the Bluehost Node.js interface
3. **Health Check**: Visit `/health` endpoint to verify server status

## Security Considerations

1. **Environment Variables**: Never commit sensitive data to version control
2. **Webhook Secret**: Use a strong, unique secret for webhook authentication
3. **HTTPS**: Ensure your domain uses SSL/HTTPS
4. **CORS**: Configure CORS settings for production domains

## Maintenance

1. **Updates**: Regularly update your dependencies
2. **Backups**: Keep backups of your application files
3. **Monitoring**: Monitor your application logs for errors
4. **Performance**: Monitor resource usage in Bluehost control panel

## Support

If you encounter issues:
1. Check Bluehost's Node.js documentation
2. Review application logs
3. Contact Bluehost support for hosting-specific issues
4. Check the application's health endpoint for debugging information 