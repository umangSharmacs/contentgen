// Test script to simulate n8n webhook requests
import fetch from 'node-fetch';

const SERVER_URL = 'http://localhost:3001';
const WEBHOOK_ENDPOINT = `${SERVER_URL}/webhook/n8n`;

// Test data examples that n8n might send
const testData = [
  {
    pmid: "12345678",
    date: "2024-01-15",
    journal: "Nature Medicine",
    tweet: "Prior CPI in Hodgkin lymphoma pts undergoing alloHCT improves PFS and lowers relapse risk, but acute GVHD risk increases—chronic GVHD unaffected. Post-transplant cyclophosphamide reduces GVHD without compromising efficacy. #HodgkinLymphoma #alloHCT #GVHD",
    doi: "10.1038/s41591-024-00001-1",
    cancerType: "Hodgkin Lymphoma",
    summary: "Study shows checkpoint inhibitors improve outcomes in Hodgkin lymphoma patients undergoing allogeneic hematopoietic cell transplantation.",
    abstract: "This study evaluated the impact of checkpoint inhibitors on outcomes in Hodgkin lymphoma patients undergoing allogeneic hematopoietic cell transplantation. Results showed improved progression-free survival and reduced relapse risk, though with increased acute graft-versus-host disease risk.",
    twitterHashtags: "#HodgkinLymphoma, #alloHCT, #GVHD, #CancerResearch",
    twitterAccounts: "@NatureMedicine, @ASCO, @CancerResearch",
    score: 8.5
  },
  {
    pmid: "87654321",
    date: "2024-01-10",
    journal: "Cell",
    tweet: "Early cancer interception is evolving—MCED assays + multidimensional biomarkers now detect risk across malignancies, not just organs. Integrating molecular, immune, and microbiome signatures enables precision prevention strategies. #CancerInterception #MCED #PrecisionMedicine",
    doi: "10.1016/j.cell.2024.01.001",
    cancerType: "Multi-Cancer",
    summary: "Multi-cancer early detection assays combined with biomarkers enable precision prevention strategies.",
    abstract: "This review discusses the evolution of early cancer interception through multi-cancer early detection assays and multidimensional biomarkers. The integration of molecular, immune, and microbiome signatures enables more precise prevention strategies across multiple cancer types.",
    twitterHashtags: "#CancerInterception, #MCED, #PrecisionMedicine, #Biomarkers",
    twitterAccounts: "@CellPressNews, @CancerResearch, @PrecisionMed",
    score: 9.2
  },
  {
    pmid: "11223344",
    date: "2024-01-05",
    journal: "JAMA Oncology",
    tweet: "Tip: Integrate survivorship care into every phase—not just post-treatment. Palliative teams can address symptom burden, psychosocial, and holistic needs from diagnosis to end of life, improving quality of life throughout the cancer journey. #Survivorship #PalliativeCare #QoL",
    doi: "10.1001/jamaoncol.2024.0001",
    cancerType: "General Oncology",
    summary: "Comprehensive survivorship care should be integrated throughout the cancer journey, not just post-treatment.",
    abstract: "This perspective article emphasizes the importance of integrating survivorship care into every phase of the cancer journey, not just the post-treatment period. Palliative care teams can address symptom burden, psychosocial needs, and holistic care from diagnosis through end of life.",
    twitterHashtags: "#Survivorship, #PalliativeCare, #QoL, #CancerCare",
    twitterAccounts: "@JAMAOnc, @ASCO, @CancerSurvivors",
    score: 7.8
  }
];

// Function to send test request
async function sendTestRequest(data, description) {
  try {
    console.log(`\n🧪 Testing: ${description}`);
    console.log('📤 Sending data:', JSON.stringify(data, null, 2));

    const response = await fetch(WEBHOOK_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        // Uncomment the next line if you have webhook secret configured
        // 'x-webhook-secret': 'your_webhook_secret_here'
      },
      body: JSON.stringify(data)
    });

    const responseData = await response.json();

    console.log(`📥 Response Status: ${response.status}`);
    console.log('📥 Response Data:', JSON.stringify(responseData, null, 2));

    if (response.ok) {
      console.log('✅ Test passed!');
    } else {
      console.log('❌ Test failed!');
    }

  } catch (error) {
    console.error('❌ Error sending test request:', error.message);
  }
}

// Function to test health endpoint
async function testHealthEndpoint() {
  try {
    console.log('\n🏥 Testing health endpoint...');
    
    const response = await fetch(`${SERVER_URL}/health`);
    const data = await response.json();
    
    console.log(`📥 Health Status: ${response.status}`);
    console.log('📥 Health Data:', JSON.stringify(data, null, 2));
    
    if (response.ok) {
      console.log('✅ Health check passed!');
    } else {
      console.log('❌ Health check failed!');
    }
    
  } catch (error) {
    console.error('❌ Error testing health endpoint:', error.message);
  }
}

// Function to test research data API
async function testResearchDataAPI() {
  try {
    console.log('\n📊 Testing research data API...');
    
    const response = await fetch(`${SERVER_URL}/api/research-data`);
    const data = await response.json();
    
    console.log(`📥 API Status: ${response.status}`);
    console.log('📥 Data Count:', data.count);
    
    if (response.ok) {
      console.log('✅ Research data API passed!');
    } else {
      console.log('❌ Research data API failed!');
    }
    
  } catch (error) {
    console.error('❌ Error testing research data API:', error.message);
  }
}

// Main test function
async function runTests() {
  console.log('🚀 Starting n8n webhook tests...');
  console.log(`📍 Server URL: ${SERVER_URL}`);
  
  // Test health endpoint first
  await testHealthEndpoint();
  
  // Test research data API
  await testResearchDataAPI();
  
  // Test different data formats
  for (let i = 0; i < testData.length; i++) {
    await sendTestRequest(testData[i], `Research Data ${i + 1}`);
  }
  
  // Test with minimal data
  await sendTestRequest({
    pmid: "99999999",
    tweet: "Minimal test tweet",
    journal: "Test Journal"
  }, 'Minimal Data');
  
  console.log('\n🎉 All tests completed!');
}

// Run tests if this file is executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  runTests().catch(console.error);
}

export { sendTestRequest, testHealthEndpoint, testResearchDataAPI, runTests }; 