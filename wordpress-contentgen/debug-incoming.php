<?php
/**
 * Debug script to log incoming webhook data and show last 10 DB rows
 * Place this file in your WordPress root directory
 */

// Load WordPress
require_once('wp-config.php');

global $wpdb;

// Log incoming data
function log_webhook_data($data) {
    $log_file = ABSPATH . 'webhook-debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] Received webhook data:\n";
    $log_entry .= json_encode($data, JSON_PRETTY_PRINT) . "\n";
    $log_entry .= "----------------------------------------\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Test the logging
if (isset($_GET['test'])) {
    $test_data = [
        'PMID' => '12345678',
        'Date ' => '2025-01-15',
        'Journal' => 'Test Journal',
        'Tweet' => 'Test tweet',
        'Tweet (Few shot learning)' => 'Test few shot tweet',
        'Specific Cancer type' => 'Test Cancer',
        'Score' => 8.5
    ];
    log_webhook_data($test_data);
    echo "Test data logged to webhook-debug.log";
    exit;
}

// Show recent logs
if (isset($_GET['view'])) {
    $log_file = ABSPATH . 'webhook-debug.log';
    if (file_exists($log_file)) {
        echo "<h1>Recent Webhook Logs</h1>";
        echo "<pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre>";
    } else {
        echo "<h1>No logs found</h1>";
        echo "<p>No webhook-debug.log file exists yet.</p>";
    }
    exit;
}

// Show last 10 DB rows
if (isset($_GET['db'])) {
    $table = $wpdb->prefix . 'contentgen_research_data';
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 10", ARRAY_A);
    echo "<h1>Last 10 Rows in contentgen_research_data</h1>";
    if ($rows) {
        echo "<pre>" . htmlspecialchars(print_r($rows, true)) . "</pre>";
    } else {
        echo "<p>No rows found in the table.</p>";
    }
    exit;
}

echo "<h1>Webhook Debug Tools</h1>";
echo "<p><a href='?test=1'>Test Logging</a></p>";
echo "<p><a href='?view=1'>View Recent Logs</a></p>";
echo "<p><a href='?db=1'>View Last 10 DB Rows</a></p>";
echo "<p><strong>Log file location:</strong> " . ABSPATH . "webhook-debug.log</p>";
?> 