<?php
/**
 * Test script for the new JSON format: {items: [JSON_obj]}
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yourdomain.com/test-new-format.php
 */

// Replace with your actual domain and secret
$domain = 'https://yourdomain.com'; // CHANGE THIS
$secret = '12345678';   // Simple secret for testing

// Test data with the exact format provided by the user
$testData = [
    'items' => [
        [
            'row_number' => 317,
            'PMID' => 40541217,
            'Date ' => '2025-07-13',
            'Journal' => 'Lancet (London, England)',
            'Tweet' => 'Cancer vaccines are moving beyond prevention, now showing activity post-surgery (melanoma, pancreatic) and in advanced settings (lung, breast, lymphomas). Consider clinical trial enrollment for eligible patients—especially as combinations with checkpoint blockade mature.',
            'Tweet (Few shot learning)' => 'Therapeutic cancer vaccines now show promise: adjuvant use reduces relapse in melanoma & pancreatic cancer; in-situ approaches trigger regression in metastatic lung, breast, and lymphoma. Next-gen design may further improve survival and quality of life in community settings.',
            'DOI' => '',
            'Specific Cancer type' => 'Early detection',
            'Summary' => 'The publication reviews the evolving role of cancer vaccines in oncology, highlighting the success of prophylactic vaccines in preventing pathogen-related cancers and the emerging promise of therapeutic vaccines for treating established tumors. It discusses their effectiveness in reducing relapse in melanoma and pancreatic cancer in the adjuvant setting, and inducing systemic tumor regression in advanced lung, breast cancers, and lymphomas through in-situ vaccines. The article emphasizes advancements driven by improved tumor immunology understanding, novel vaccine components, omics, artificial intelligence, and combination with immune checkpoint blockers. It evaluates current vaccine trials, their strengths and limitations, and explores how next-generation vaccines could enhance patient outcomes and quality of life.',
            'Abstract' => 'Vaccines have had a major impact on the control of infectious disease, most recently by helping to combat the COVID-19 pandemic. Prophylactic cancer vaccines have prevented several malignancies by protecting against cancer-causing pathogens. By contrast, therapeutic vaccines training the immune system to eliminate established tumours are now showing real promise in clinical settings. In the adjuvant setting, vaccines against melanoma and pancreatic cancer appear to be reducing minimal residual disease and relapse. In the macrometastatic setting, in-situ vaccines have induced systemic regressions in advanced-stage lung and breast cancers and lymphomas. More effective cancer vaccines are being developed through having a deeper understanding of crucial cellular factors in tumour immunology, the incorporation of newer vaccine components to effectively mobilise and activate cells, the use of omics and artificial intelligence in vaccine design, and addition of immune checkpoint blockade. In this Viewpoint, we analyse cancer vaccine trials, the strengths and limitations of different vaccine approaches, and we discuss how the next generation of cancer vaccines can help improve patient outcomes and quality of life.',
            'Twiter Hashtags' => '#cancerdetection, #earlydetection',
            'Twiiter accounts tagged' => '@lpetrillz, @mattgonzalesmd, @crisbergerot, @RyanNipp, @NicoleStoutPT, @ramsedhom, @maryam_lustberg, @DrN_CancerPCP, @DrNicolasHart',
            'Score' => 8.33,
            'Selected' => ''
        ]
    ]
];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Webhook-Secret: ' . $secret
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Display results
echo "<h1>New Format Webhook Test Results</h1>";
echo "<p><strong>URL:</strong> " . $domain . '/wp-admin/admin-ajax.php?action=contentgen_webhook' . "</p>";
echo "<p><strong>Secret Used:</strong> " . $secret . "</p>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Format:</strong> {items: [JSON_obj]}</p>";
echo "<p><strong>Items Sent:</strong> " . count($testData['items']) . "</p>";

if ($error) {
    echo "<p><strong>cURL Error:</strong> " . $error . "</p>";
} else {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Decode and display JSON response
$responseData = json_decode($response, true);
if ($responseData) {
    echo "<p><strong>Decoded Response:</strong></p>";
    echo "<pre>" . print_r($responseData, true) . "</pre>";
    
    // Display summary if available
    if (isset($responseData['data']['processed_count'])) {
        echo "<h2>Processing Summary</h2>";
        echo "<p><strong>Successfully Processed:</strong> " . $responseData['data']['processed_count'] . "</p>";
        echo "<p><strong>Failed:</strong> " . $responseData['data']['failed_count'] . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Test Data Sent:</strong></p>";
echo "<pre>" . print_r($testData, true) . "</pre>";

echo "<hr>";
echo "<h2>Field Mapping</h2>";
echo "<p>The plugin will map these fields:</p>";
echo "<ul>";
echo "<li><strong>PMID</strong> → pmid</li>";
echo "<li><strong>Date </strong> → date_added</li>";
echo "<li><strong>Journal</strong> → journal</li>";
echo "<li><strong>Tweet</strong> → tweet</li>";
echo "<li><strong>Tweet (Few shot learning)</strong> → tweet_few_shot</li>";
echo "<li><strong>DOI</strong> → doi</li>";
echo "<li><strong>Specific Cancer type</strong> → cancer_type</li>";
echo "<li><strong>Summary</strong> → summary</li>";
echo "<li><strong>Abstract</strong> → abstract</li>";
echo "<li><strong>Twiter Hashtags</strong> → twitter_hashtags</li>";
echo "<li><strong>Twiiter accounts tagged</strong> → twitter_accounts</li>";
echo "<li><strong>Score</strong> → score</li>";
echo "</ul>";
?> 