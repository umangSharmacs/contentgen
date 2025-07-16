import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import process from 'process';

// Load environment variables
dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3001;

// In-memory storage for research data
let researchData = [];
let acceptedTweets = [];

// Middleware
app.use(helmet()); // Security headers
app.use(cors()); // Enable CORS
app.use(express.json({ limit: '10mb' })); // Parse JSON bodies
app.use(express.urlencoded({ extended: true }));

// Serve static files from the React app
app.use(express.static(path.join(__dirname, 'dist')));

// Health check endpoint
app.get('/health', (req, res) => {
  res.status(200).json({ 
    status: 'OK', 
    message: 'Server is running',
    timestamp: new Date().toISOString(),
    dataCount: researchData.length,
    acceptedTweetsCount: acceptedTweets.length
  });
});

// Webhook endpoint to receive data from n8n
app.post('/webhook/n8n', (req, res) => {
  try {
    const { body, headers } = req;
    
    // Log the incoming request
    console.log('Received webhook from n8n:');
    console.log('Headers:', headers);
    console.log('Body:', JSON.stringify(body, null, 2));
    
    // Validate the request
    if (!body) {
      return res.status(400).json({ 
        error: 'No data received',
        timestamp: new Date().toISOString()
      });
    }
    
    // Process the incoming data
    const processedData = processIncomingData(body);
    
    // Add to research data
    researchData.push(processedData);
    
    console.log('Processed and stored data:', processedData);
    console.log('Total research data entries:', researchData.length);
    
    // Send success response
    res.status(200).json({
      success: true,
      message: 'Data received and processed successfully',
      data: processedData,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('Error processing webhook:', error);
    res.status(500).json({
      error: 'Internal server error',
      message: error.message,
      timestamp: new Date().toISOString()
    });
  }
});

// API endpoint to get research data
app.get('/api/research-data', (req, res) => {
  try {
    res.status(200).json({
      success: true,
      data: researchData,
      count: researchData.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Error retrieving research data:', error);
    res.status(500).json({
      error: 'Internal server error',
      message: error.message,
      timestamp: new Date().toISOString()
    });
  }
});

// API endpoint to export accepted tweets
app.post('/api/export-tweets', (req, res) => {
  try {
    const { body } = req;
    
    if (!body || !Array.isArray(body)) {
      return res.status(400).json({
        error: 'Invalid data format',
        message: 'Expected array of tweets',
        timestamp: new Date().toISOString()
      });
    }
    
    // Store accepted tweets
    acceptedTweets = [...acceptedTweets, ...body];
    
    console.log('Exported tweets:', body);
    console.log('Total accepted tweets:', acceptedTweets.length);
    
    res.status(200).json({
      success: true,
      message: 'Tweets exported successfully',
      count: body.length,
      totalAccepted: acceptedTweets.length,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('Error exporting tweets:', error);
    res.status(500).json({
      error: 'Internal server error',
      message: error.message,
      timestamp: new Date().toISOString()
    });
  }
});

// API endpoint to get accepted tweets
app.get('/api/accepted-tweets', (req, res) => {
  try {
    res.status(200).json({
      success: true,
      data: acceptedTweets,
      count: acceptedTweets.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Error retrieving accepted tweets:', error);
    res.status(500).json({
      error: 'Internal server error',
      message: error.message,
      timestamp: new Date().toISOString()
    });
  }
});

// Function to process incoming data from n8n
function processIncomingData(data) {
  const timestamp = new Date().toISOString();
  
  // Generate a unique ID if not provided
  const id = data.id || `research_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  
  // Process the data to match expected format
  const processed = {
    id,
    pmid: data.pmid || data.PMID || '',
    date: data.date || data.Date || data['Date Added'] || timestamp.split('T')[0],
    journal: data.journal || data.Journal || '',
    tweet: data.tweet || data.Tweet || '',
    doi: data.doi || data.DOI || '',
    cancerType: data.cancerType || data['Cancer Type'] || data['Specific Cancer type'] || '',
    summary: data.summary || data.Summary || '',
    abstract: data.abstract || data.Abstract || '',
    twitterHashtags: data.twitterHashtags || data['Twitter Hashtags'] || data['Twitter Tags'] || '',
    twitterAccounts: data.twitterAccounts || data['Twitter accounts tagged'] || data['People to tag'] || '',
    score: parseFloat(data.score || data.Score || 0) || 0,
    receivedAt: timestamp,
    source: 'n8n'
  };
  
  return processed;
}

// Error handling middleware
app.use((err, req, res, _next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({
    error: 'Internal server error',
    message: 'Something went wrong',
    timestamp: new Date().toISOString()
  });
});

// Serve React app for all other routes
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'dist/index.html'));
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
  console.log(`📡 Webhook endpoint: http://localhost:${PORT}/webhook/n8n`);
  console.log(`🏥 Health check: http://localhost:${PORT}/health`);
  console.log(`📊 Research data API: http://localhost:${PORT}/api/research-data`);
  console.log(`🌐 Frontend: http://localhost:${PORT}`);
}); 