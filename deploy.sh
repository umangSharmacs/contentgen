#!/bin/bash

# Deployment script for Bluehost
echo "🚀 Starting deployment process..."

# Build the React application
echo "📦 Building React application..."
npm run build

# Create deployment directory
echo "📁 Creating deployment package..."
mkdir -p deploy
cp -r dist/* deploy/
cp server/server.js deploy/
cp server/package.json deploy/
cp server/env.example deploy/.env

# Create .htaccess for Bluehost
echo "📝 Creating .htaccess file..."
cat > deploy/.htaccess << 'EOF'
RewriteEngine On

# Handle Node.js applications
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ server.js [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
EOF

echo "✅ Deployment package created in 'deploy' directory"
echo "📋 Next steps:"
echo "1. Upload the contents of 'deploy' folder to your Bluehost public_html directory"
echo "2. Set up Node.js in your Bluehost control panel"
echo "3. Configure your domain to point to the application" 