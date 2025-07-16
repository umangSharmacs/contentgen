#!/bin/bash

# ContentGen Backend Setup Script

echo "🚀 Setting up ContentGen Backend Server..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

echo "✅ Node.js is installed: $(node --version)"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ npm is installed: $(npm --version)"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ Failed to install dependencies"
    exit 1
fi

echo "✅ Dependencies installed successfully"

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp env.example .env
    echo "✅ .env file created from template"
    echo "⚠️  Please edit .env file with your configuration"
else
    echo "✅ .env file already exists"
fi

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your configuration (optional)"
echo "2. Build the frontend: cd .. && npm run build"
echo "3. Start the server: npm run dev"
echo "4. Test the webhook: npm test"
echo ""
echo "Server will be available at:"
echo "  - Frontend: http://localhost:3001"
echo "  - Health check: http://localhost:3001/health"
echo "  - Webhook endpoint: http://localhost:3001/webhook/n8n"
echo "  - Research data API: http://localhost:3001/api/research-data" 